<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\External;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('002-phpunit-coverage-parser')]
final class PhpUnitCoverageOutputTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/test-selector-coverage-'.bin2hex(random_bytes(8));
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function xmlOutputContainsExpectedStructure(): void
    {
        $coverageDir = $this->tempDir.'/coverage-xml';

        $this->runPhpUnitWithCoverage($coverageDir);

        $indexXml = new \SimpleXMLElement($this->readFile($coverageDir.'/index.xml'));
        $indexXml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

        // index.xml lists all tests
        $tests = $this->xpath($indexXml, '//p:project/p:tests/p:test');
        self::assertCount(2, $tests);

        $testNames = array_map(
            static fn (\SimpleXMLElement $test): string => (string) $test['name'],
            $tests,
        );
        sort($testNames);
        self::assertSame([
            'Fixtures\Tests\CalculatorTest::add',
            'Fixtures\Tests\GreeterTest::greet',
        ], $testNames);

        // index.xml lists per-file references
        $files = $this->xpath($indexXml, '//p:project/p:directory/p:file');
        self::assertCount(2, $files);

        $fileNames = array_map(
            static fn (\SimpleXMLElement $file): string => (string) $file['name'],
            $files,
        );
        sort($fileNames);
        self::assertSame(['Calculator.php', 'Greeter.php'], $fileNames);
    }

    #[Test]
    public function xmlFileOutputContainsPerTestLineCoverage(): void
    {
        $coverageDir = $this->tempDir.'/coverage-xml';

        $this->runPhpUnitWithCoverage($coverageDir);

        // Validate Calculator.php.xml — line 11 covered by CalculatorTest::add
        $calcXml = new \SimpleXMLElement($this->readFile($coverageDir.'/Calculator.php.xml'));
        $calcXml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

        $calcCoveredLines = $this->xpath($calcXml, '//p:file/p:coverage/p:line');
        self::assertCount(1, $calcCoveredLines);
        self::assertSame('11', (string) $calcCoveredLines[0]['nr']);

        $calcCoveredBy = $this->xpath($calcXml, '//p:file/p:coverage/p:line/p:covered');
        self::assertCount(1, $calcCoveredBy);
        self::assertSame('Fixtures\Tests\CalculatorTest::add', (string) $calcCoveredBy[0]['by']);

        // Validate Greeter.php.xml — line 11 covered by GreeterTest::greet
        $greetXml = new \SimpleXMLElement($this->readFile($coverageDir.'/Greeter.php.xml'));
        $greetXml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

        $greetCoveredLines = $this->xpath($greetXml, '//p:file/p:coverage/p:line');
        self::assertCount(1, $greetCoveredLines);
        self::assertSame('11', (string) $greetCoveredLines[0]['nr']);

        $greetCoveredBy = $this->xpath($greetXml, '//p:file/p:coverage/p:line/p:covered');
        self::assertCount(1, $greetCoveredBy);
        self::assertSame('Fixtures\Tests\GreeterTest::greet', (string) $greetCoveredBy[0]['by']);
    }

    #[Test]
    public function xmlFileOutputContainsFileName(): void
    {
        $coverageDir = $this->tempDir.'/coverage-xml';

        $this->runPhpUnitWithCoverage($coverageDir);

        $calcXml = new \SimpleXMLElement($this->readFile($coverageDir.'/Calculator.php.xml'));
        $calcXml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

        $fileElements = $this->xpath($calcXml, '//p:file');
        self::assertCount(1, $fileElements);
        self::assertSame('Calculator.php', (string) $fileElements[0]['name']);
        self::assertSame('/', (string) $fileElements[0]['path']);
    }

    private function runPhpUnitWithCoverage(string $outputDir): void
    {
        $phpunit = realpath(__DIR__.'/../../vendor/bin/phpunit');
        self::assertNotFalse($phpunit);

        $fixturesDir = realpath(__DIR__.'/Fixtures');
        self::assertNotFalse($fixturesDir);

        $command = sprintf(
            'cd %s && XDEBUG_MODE=coverage php %s --configuration %s/phpunit.xml --coverage-xml %s 2>&1',
            escapeshellarg($fixturesDir),
            escapeshellarg($phpunit),
            escapeshellarg($fixturesDir),
            escapeshellarg($outputDir),
        );

        exec($command, $output, $exitCode); // @phpstan-ignore disallowed.function

        self::assertSame(0, $exitCode, sprintf("PHPUnit failed:\n%s", implode("\n", $output)));
    }

    /**
     * @return list<\SimpleXMLElement>
     */
    private function xpath(\SimpleXMLElement $xml, string $expression): array
    {
        $result = $xml->xpath($expression);

        return \is_array($result) ? array_values($result) : [];
    }

    private function readFile(string $path): string
    {
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        return $content;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
