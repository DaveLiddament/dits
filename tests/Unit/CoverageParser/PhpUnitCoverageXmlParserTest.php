<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\CoverageParser;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\CoverageParser\PhpUnitCoverageXmlParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('002-phpunit-coverage-parser')]
final class PhpUnitCoverageXmlParserTest extends TestCase
{
    private const string FIXTURES_DIR = __DIR__.'/Fixtures';
    private const string SOURCE_PREFIX = 'src/';

    private PhpUnitCoverageXmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PhpUnitCoverageXmlParser();
    }

    #[Test]
    public function parsesFixtureXmlIntoTestCoverageReport(): void
    {
        $commitIdentifier = new CommitIdentifier('abc123');

        $report = $this->parser->parse(self::FIXTURES_DIR, $commitIdentifier, self::SOURCE_PREFIX);

        self::assertSame($commitIdentifier, $report->commitIdentifier);
        self::assertCount(3, $report->testCoverages);
    }

    #[Test]
    public function parsesCorrectTestNames(): void
    {
        $report = $this->parser->parse(self::FIXTURES_DIR, new CommitIdentifier('abc123'), self::SOURCE_PREFIX);

        $testNames = array_map(
            static fn ($tc): string => $tc->testName->testName,
            $report->testCoverages,
        );
        sort($testNames);

        self::assertSame([
            'App\Tests\CalculatorTest::add',
            'App\Tests\GreeterTest::greet',
            'App\Tests\LoggerTest::log',
        ], $testNames);
    }

    #[Test]
    public function parsesCorrectLineCoverageForRootLevelFile(): void
    {
        $report = $this->parser->parse(self::FIXTURES_DIR, new CommitIdentifier('abc123'), self::SOURCE_PREFIX);

        $calculatorCoverage = $this->findTestCoverage($report->testCoverages, 'App\Tests\CalculatorTest::add');

        self::assertCount(1, $calculatorCoverage->lineCoverages);
        self::assertSame('src/Calculator.php', $calculatorCoverage->lineCoverages[0]->fileName);
        self::assertSame(11, $calculatorCoverage->lineCoverages[0]->lineNumber);
    }

    #[Test]
    public function parsesCorrectLineCoverageForNestedFile(): void
    {
        $report = $this->parser->parse(self::FIXTURES_DIR, new CommitIdentifier('abc123'), self::SOURCE_PREFIX);

        $loggerCoverage = $this->findTestCoverage($report->testCoverages, 'App\Tests\LoggerTest::log');

        self::assertCount(1, $loggerCoverage->lineCoverages);
        self::assertSame('src/Service/Logger.php', $loggerCoverage->lineCoverages[0]->fileName);
        self::assertSame(8, $loggerCoverage->lineCoverages[0]->lineNumber);
    }

    #[Test]
    public function emptySourcePrefixProducesPathsRelativeToSourceRoot(): void
    {
        $report = $this->parser->parse(self::FIXTURES_DIR, new CommitIdentifier('abc123'), '');

        $calculatorCoverage = $this->findTestCoverage($report->testCoverages, 'App\Tests\CalculatorTest::add');

        self::assertSame('Calculator.php', $calculatorCoverage->lineCoverages[0]->fileName);
    }

    #[Test]
    public function skipsMissingFileXml(): void
    {
        $report = $this->parser->parse(self::FIXTURES_DIR.'/missing-file', new CommitIdentifier('abc123'), self::SOURCE_PREFIX);

        self::assertCount(0, $report->testCoverages);
    }

    #[Test]
    public function throwsWhenIndexXmlMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->parser->parse('/nonexistent/path', new CommitIdentifier('abc123'), self::SOURCE_PREFIX);
    }

    /**
     * @param list<\DaveLiddament\TestSelector\Coverage\TestCoverage> $testCoverages
     */
    private function findTestCoverage(array $testCoverages, string $testName): \DaveLiddament\TestSelector\Coverage\TestCoverage
    {
        foreach ($testCoverages as $testCoverage) {
            if ($testCoverage->testName->testName === $testName) {
                return $testCoverage;
            }
        }

        self::fail(sprintf('TestCoverage not found for test: %s', $testName));
    }
}
