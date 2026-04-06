<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\EndToEnd;

use DaveLiddament\TestSelector\Command\DitsCommand;
use DaveLiddament\TestSelector\Command\GenerateTcrCommand;
use DaveLiddament\TestSelector\DiffFinder\ProcessGitCommandRunner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Full pipeline test with a real git repository.
 *
 * Uses the coverage XML fixtures which report:
 *   - src/Calculator.php line 11 covered by App\Tests\CalculatorTest::add
 *   - src/Greeter.php line 11 covered by App\Tests\GreeterTest::greet
 *   - src/Service/Logger.php line 8 covered by App\Tests\LoggerTest::log
 */
#[Ticket('011-end-to-end')]
final class EndToEndTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/dits-e2e-'.bin2hex(random_bytes(8));
        mkdir($this->tempDir, 0o777, true);
        mkdir($this->tempDir.'/src', 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function modifiedFileSelectsMatchingTest(): void
    {
        $this->initRepo();
        $this->writeSourceFiles();
        $this->git('add', '.');
        $this->git('commit', '-m', 'Initial commit');

        $tcrJson = $this->generateTcr();

        // Commit the TCR so it's not untracked
        file_put_contents($this->tempDir.'/tcr.json', $tcrJson);
        $this->git('add', '.');
        $this->git('commit', '-m', 'Add TCR');

        // Modify line 11 of Calculator.php (the covered line)
        $this->modifyFileLine($this->tempDir.'/src/Calculator.php', 11, '        return $a + $b + 0;');
        $this->git('add', '.');
        $this->git('commit', '-m', 'Modify Calculator');

        $output = $this->runDits($tcrJson);

        self::assertStringContainsString('App\Tests\CalculatorTest::add', $output);
        self::assertStringNotContainsString('App\Tests\GreeterTest::greet', $output);
    }

    #[Test]
    public function newFileSelectsNothingWhenNotInCoverage(): void
    {
        $this->initRepo();
        $this->writeSourceFiles();
        $this->git('add', '.');
        $this->git('commit', '-m', 'Initial commit');

        $tcrJson = $this->generateTcr();

        file_put_contents($this->tempDir.'/tcr.json', $tcrJson);
        $this->git('add', '.');
        $this->git('commit', '-m', 'Add TCR');

        // Add a new file not in coverage
        file_put_contents($this->tempDir.'/src/NewClass.php', "<?php\nclass NewClass {}\n");
        $this->git('add', '.');
        $this->git('commit', '-m', 'Add new file');

        $output = $this->runDits($tcrJson);

        // New file has no coverage data so no tests selected
        self::assertSame('', $output);
    }

    #[Test]
    public function noChangesSelectsNoTests(): void
    {
        $this->initRepo();
        $this->writeSourceFiles();
        $this->git('add', '.');
        $this->git('commit', '-m', 'Initial commit');

        $tcrJson = $this->generateTcr();

        // No further changes — dits should select nothing
        $output = $this->runDits($tcrJson);

        self::assertSame('', $output);
    }

    private function initRepo(): void
    {
        $this->git('init');
        $this->git('config', 'user.email', 'test@test.com');
        $this->git('config', 'user.name', 'Test');
    }

    /**
     * Write source files with enough lines so that line 11 exists and is meaningful.
     * The coverage fixtures say line 11 of Calculator.php and Greeter.php are covered.
     */
    private function writeSourceFiles(): void
    {
        file_put_contents($this->tempDir.'/src/Calculator.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App;

            class Calculator
            {
                public function add(int $a, int $b): int
                {
                    return $a + $b;
                }

                public function subtract(int $a, int $b): int
                {
                    return $a - $b;
                }
            }
            PHP);

        file_put_contents($this->tempDir.'/src/Greeter.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App;

            class Greeter
            {
                public function greet(string $name): string
                {
                    return 'Hello, ' . $name;
                }
            }
            PHP);
    }

    private function generateTcr(): string
    {
        $coverageFixturesDir = realpath(__DIR__.'/../Unit/CoverageParser/Fixtures');
        self::assertNotFalse($coverageFixturesDir);

        $commitSha = trim($this->gitOutput('rev-parse', 'HEAD'));

        $application = new Application();
        $application->add(new GenerateTcrCommand());
        $tester = new CommandTester($application->find('generate-tcr'));

        $tester->execute([
            'coverage-xml-dir' => $coverageFixturesDir,
            '--source-dir' => 'src/',
            '--commit' => $commitSha,
        ]);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());

        return $tester->getDisplay();
    }

    private function runDits(string $tcrJson): string
    {
        $gitRunner = new ProcessGitCommandRunner($this->tempDir);
        $ditsCommand = new DitsCommand(gitCommandRunner: $gitRunner, stdinOverride: $tcrJson);

        $application = new Application();
        $application->add($ditsCommand);
        $tester = new CommandTester($application->find('dits'));

        $tester->execute(['--format' => 'list']);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());

        return trim($tester->getDisplay());
    }

    private function modifyFileLine(string $filePath, int $lineNumber, string $newContent): void
    {
        $lines = file($filePath);
        self::assertNotFalse($lines);
        self::assertArrayHasKey($lineNumber - 1, $lines);
        $lines[$lineNumber - 1] = $newContent."\n";
        file_put_contents($filePath, implode('', $lines));
    }

    private function git(string ...$args): void
    {
        $command = sprintf(
            'cd %s && git %s 2>&1',
            escapeshellarg($this->tempDir),
            implode(' ', array_map(escapeshellarg(...), $args)),
        );
        exec($command, $output, $exitCode); // @phpstan-ignore disallowed.function
        self::assertSame(0, $exitCode, sprintf("git %s failed:\n%s", implode(' ', $args), implode("\n", $output)));
    }

    private function gitOutput(string ...$args): string
    {
        $command = sprintf(
            'cd %s && git %s 2>&1',
            escapeshellarg($this->tempDir),
            implode(' ', array_map(escapeshellarg(...), $args)),
        );
        exec($command, $output, $exitCode); // @phpstan-ignore disallowed.function
        self::assertSame(0, $exitCode, sprintf("git %s failed:\n%s", implode(' ', $args), implode("\n", $output)));

        return implode("\n", $output);
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
