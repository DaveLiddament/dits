<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Command;

use DaveLiddament\TestSelector\Command\DitsCommand;
use DaveLiddament\TestSelector\Tests\Unit\DiffFinder\FakeGitCommandRunner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[Ticket('009-dits-command')]
final class DitsCommandTest extends TestCase
{
    private const string TCR_JSON = <<<'JSON'
        {
            "version": 1,
            "commitIdentifier": "abc123",
            "testCoverages": [
                {
                    "testName": "App\\Tests\\FooTest::testBar",
                    "lineCoverages": [
                        {"fileName": "src/Foo.php", "lineNumber": 10}
                    ]
                },
                {
                    "testName": "App\\Tests\\BazTest::testQux",
                    "lineCoverages": [
                        {"fileName": "src/Baz.php", "lineNumber": 5}
                    ]
                }
            ]
        }
        JSON;

    #[Test]
    public function listFormatOutputsOneTestPerLine(): void
    {
        $commandTester = $this->createCommandTester(
            self::TCR_JSON,
            ["M\tsrc/Foo.php"],
            $this->modifiedFileDiff('src/Foo.php', 10),
        );

        $commandTester->execute(['--format' => 'list']);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertStringContainsString('App\Tests\FooTest::testBar', $commandTester->getDisplay());
        self::assertStringNotContainsString('App\Tests\BazTest::testQux', $commandTester->getDisplay());
    }

    #[Test]
    public function phpunitFilterFormatOutputsFilterFlag(): void
    {
        $commandTester = $this->createCommandTester(
            self::TCR_JSON,
            ["M\tsrc/Foo.php"],
            $this->modifiedFileDiff('src/Foo.php', 10),
        );

        $commandTester->execute(['--format' => 'phpunit-filter']);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = trim($commandTester->getDisplay());
        self::assertStringStartsWith("--filter='", $output);
        self::assertStringContainsString('App\\\\Tests\\\\FooTest::testBar', $output);
    }

    #[Test]
    public function jsonFormatOutputsJsonArray(): void
    {
        $commandTester = $this->createCommandTester(
            self::TCR_JSON,
            ["M\tsrc/Foo.php"],
            $this->modifiedFileDiff('src/Foo.php', 10),
        );

        $commandTester->execute(['--format' => 'json']);

        self::assertSame(0, $commandTester->getStatusCode());
        $decoded = json_decode(trim($commandTester->getDisplay()), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame(['App\Tests\FooTest::testBar'], $decoded);
    }

    #[Test]
    public function multipleTestsSelected(): void
    {
        $commandTester = $this->createCommandTester(
            self::TCR_JSON,
            [
                "M\tsrc/Foo.php",
                "A\tsrc/Baz.php",
            ],
            $this->modifiedFileDiff('src/Foo.php', 10),
        );

        $commandTester->execute(['--format' => 'list']);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertStringContainsString('App\Tests\FooTest::testBar', $commandTester->getDisplay());
        self::assertStringContainsString('App\Tests\BazTest::testQux', $commandTester->getDisplay());
    }

    #[Test]
    public function noTestsSelectedProducesEmptyOutput(): void
    {
        $commandTester = $this->createCommandTester(
            self::TCR_JSON,
            [],
            [],
        );

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertSame('', trim($commandTester->getDisplay()));
    }

    #[Test]
    public function emptyStdinReturnsInputError(): void
    {
        $gitRunner = new FakeGitCommandRunner();
        $commandTester = $this->buildCommandTester('', $gitRunner);

        $commandTester->execute([]);

        self::assertSame(1, $commandTester->getStatusCode());
        self::assertStringContainsString('No input', $commandTester->getDisplay());
    }

    #[Test]
    public function invalidJsonReturnsInputError(): void
    {
        $gitRunner = new FakeGitCommandRunner();
        $commandTester = $this->buildCommandTester('not valid json {{{', $gitRunner);

        $commandTester->execute([]);

        self::assertSame(1, $commandTester->getStatusCode());
        self::assertStringContainsString('Failed to parse', $commandTester->getDisplay());
    }

    #[Test]
    public function unsupportedTcrVersionShowsClearError(): void
    {
        $futureVersionJson = json_encode([
            'version' => 99,
            'commitIdentifier' => 'abc123',
            'testCoverages' => [],
        ]);
        self::assertNotFalse($futureVersionJson);

        $gitRunner = new FakeGitCommandRunner();
        $commandTester = $this->buildCommandTester($futureVersionJson, $gitRunner);

        $commandTester->execute([]);

        self::assertSame(1, $commandTester->getStatusCode());
        self::assertStringContainsString('Unsupported TCR version 99', $commandTester->getDisplay());
    }

    #[Test]
    public function gitErrorReturnsExitCode2(): void
    {
        $gitRunner = new FakeGitCommandRunner();
        // Don't add any responses — DiffFinder will get empty name-status and ls-files
        // But we need the git runner to throw for a real git error scenario.
        // Actually FakeGitCommandRunner returns [] for unknown commands, which won't error.
        // Let me use a runner that throws.
        $throwingRunner = new class implements \DaveLiddament\TestSelector\DiffFinder\GitCommandRunner {
            public function run(array $args): array
            {
                throw new \RuntimeException('git command failed');
            }
        };

        $commandTester = $this->buildCommandTester(self::TCR_JSON, $throwingRunner);

        $commandTester->execute([]);

        self::assertSame(2, $commandTester->getStatusCode());
        self::assertStringContainsString('Git diff failed', $commandTester->getDisplay());
    }

    #[Test]
    public function missingCommitGivesHelpfulError(): void
    {
        $throwingRunner = new class implements \DaveLiddament\TestSelector\DiffFinder\GitCommandRunner {
            public function run(array $args): array
            {
                throw new \RuntimeException("fatal: bad revision 'abc123'");
            }
        };

        $commandTester = $this->buildCommandTester(self::TCR_JSON, $throwingRunner);

        $commandTester->execute([]);

        self::assertSame(2, $commandTester->getStatusCode());
        self::assertStringContainsString('abc123 not found in local repo', $commandTester->getDisplay());
        self::assertStringContainsString('Run all tests', $commandTester->getDisplay());
    }

    #[Test]
    public function phpunitFilterWithMultipleTests(): void
    {
        $commandTester = $this->createCommandTester(
            self::TCR_JSON,
            [
                "M\tsrc/Foo.php",
                "A\tsrc/Baz.php",
            ],
            $this->modifiedFileDiff('src/Foo.php', 10),
        );

        $commandTester->execute(['--format' => 'phpunit-filter']);

        $output = trim($commandTester->getDisplay());
        self::assertStringContainsString('|', $output);
        self::assertStringContainsString('App\\\\Tests\\\\FooTest::testBar', $output);
        self::assertStringContainsString('App\\\\Tests\\\\BazTest::testQux', $output);
    }

    #[Test]
    public function defaultFormatIsList(): void
    {
        $commandTester = $this->createCommandTester(
            self::TCR_JSON,
            ["M\tsrc/Foo.php"],
            $this->modifiedFileDiff('src/Foo.php', 10),
        );

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        // No --filter, no json brackets — just plain test names
        self::assertStringNotContainsString('--filter', $commandTester->getDisplay());
        self::assertStringNotContainsString('[', $commandTester->getDisplay());
        self::assertStringContainsString('App\Tests\FooTest::testBar', $commandTester->getDisplay());
    }

    /**
     * @param list<string> $nameStatusOutput
     * @param list<string> $diffOutput
     */
    private function createCommandTester(string $tcrJson, array $nameStatusOutput, array $diffOutput): CommandTester
    {
        $gitRunner = new FakeGitCommandRunner();
        $gitRunner->addResponse('--name-status', $nameStatusOutput);
        $gitRunner->addResponse('ls-files', []);

        if ([] !== $diffOutput) {
            $gitRunner->addResponse('--unified=0', $diffOutput);
        }

        return $this->buildCommandTester($tcrJson, $gitRunner);
    }

    private function buildCommandTester(string $stdinContent, \DaveLiddament\TestSelector\DiffFinder\GitCommandRunner $gitRunner): CommandTester
    {
        $application = new Application();
        $application->add(new DitsCommand(
            gitCommandRunner: $gitRunner,
            stdinOverride: $stdinContent,
        ));
        $command = $application->find('dits');

        return new CommandTester($command);
    }

    /**
     * @return list<string>
     */
    private function modifiedFileDiff(string $fileName, int $lineNumber): array
    {
        return [
            sprintf('diff --git a/%s b/%s', $fileName, $fileName),
            sprintf('--- a/%s', $fileName),
            sprintf('+++ b/%s', $fileName),
            sprintf('@@ -%d,1 +%d,1 @@', $lineNumber, $lineNumber),
            '-old line',
            '+new line',
        ];
    }
}
