<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Command;

use DaveLiddament\TestSelector\Command\MergeTcrCommand;
use DaveLiddament\TestSelector\Serializer\TestCoverageReportSerializer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[Ticket('008-merge-tcr-command')]
final class MergeTcrCommandTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/Fixtures';

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add(new MergeTcrCommand());
        $command = $application->find('merge-tcr');
        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function mergesTwoFiles(): void
    {
        $this->commandTester->execute([
            'tcr-files' => [
                self::FIXTURES.'/tcr1.json',
                self::FIXTURES.'/tcr2.json',
            ],
        ]);

        self::assertSame(0, $this->commandTester->getStatusCode());

        $serializer = new TestCoverageReportSerializer();
        $report = $serializer->fromJson($this->commandTester->getDisplay());

        self::assertSame('abc123', $report->commitIdentifier->identifier);
        self::assertCount(2, $report->testCoverages);

        $testNames = array_map(
            static fn ($tc): string => $tc->testName->testName,
            $report->testCoverages,
        );
        sort($testNames);
        self::assertSame([
            'App\\Tests\\BazTest::testQux',
            'App\\Tests\\FooTest::testBar',
        ], $testNames);
    }

    #[Test]
    public function failsWithFewerThanTwoFiles(): void
    {
        $this->commandTester->execute([
            'tcr-files' => [self::FIXTURES.'/tcr1.json'],
        ]);

        self::assertSame(1, $this->commandTester->getStatusCode());
        self::assertStringContainsString('At least 2', $this->commandTester->getDisplay());
    }

    #[Test]
    public function failsWhenFileDoesNotExist(): void
    {
        $this->commandTester->execute([
            'tcr-files' => [
                self::FIXTURES.'/tcr1.json',
                '/nonexistent/file.json',
            ],
        ]);

        self::assertSame(1, $this->commandTester->getStatusCode());
        self::assertStringContainsString('File not found', $this->commandTester->getDisplay());
    }

    #[Test]
    public function failsWhenCommitIdentifiersDiffer(): void
    {
        $this->commandTester->execute([
            'tcr-files' => [
                self::FIXTURES.'/tcr1.json',
                self::FIXTURES.'/tcr_different_commit.json',
            ],
        ]);

        self::assertSame(1, $this->commandTester->getStatusCode());
    }

    #[Test]
    public function failsWithClearErrorWhenTcrVersionUnsupported(): void
    {
        $this->commandTester->execute([
            'tcr-files' => [
                self::FIXTURES.'/tcr1.json',
                self::FIXTURES.'/tcr_bad_version.json',
            ],
        ]);

        self::assertSame(1, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Failed to parse', $this->commandTester->getDisplay());
        self::assertStringContainsString('tcr_bad_version.json', $this->commandTester->getDisplay());
        self::assertStringContainsString('Unsupported TCR version 99', $this->commandTester->getDisplay());
    }
}
