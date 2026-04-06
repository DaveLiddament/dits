<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Command;

use DaveLiddament\TestSelector\Command\GenerateTcrCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[Ticket('007-generate-tcr-command')]
final class GenerateTcrCommandTest extends TestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add(new GenerateTcrCommand());
        $command = $application->find('generate-tcr');
        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function generatesJsonFromCoverageXml(): void
    {
        $fixturesDir = __DIR__.'/../CoverageParser/Fixtures';

        $this->commandTester->execute([
            'coverage-xml-dir' => $fixturesDir,
            '--source-dir' => 'src/',
            '--commit' => 'abc123',
        ]);

        self::assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $decoded = json_decode($output, true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertSame('abc123', $decoded['commitIdentifier']);
        self::assertCount(3, $decoded['testCoverages']);
    }

    #[Test]
    public function usesDefaultSourceDir(): void
    {
        $fixturesDir = __DIR__.'/../CoverageParser/Fixtures';

        $this->commandTester->execute([
            'coverage-xml-dir' => $fixturesDir,
            '--commit' => 'def456',
        ]);

        self::assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $decoded = json_decode($output, true, 512, \JSON_THROW_ON_ERROR);

        // Default source-dir is src/, so file paths should be prefixed
        self::assertStringStartsWith('src/', $decoded['testCoverages'][0]['lineCoverages'][0]['fileName']);
    }

    #[Test]
    public function usesGitRevParseWhenNoCommitProvided(): void
    {
        $fixturesDir = __DIR__.'/../CoverageParser/Fixtures';

        $this->commandTester->execute([
            'coverage-xml-dir' => $fixturesDir,
            '--source-dir' => 'src/',
        ]);

        self::assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $decoded = json_decode($output, true, 512, \JSON_THROW_ON_ERROR);

        // Should have resolved a real git SHA (we're in a git repo)
        self::assertNotEmpty($decoded['commitIdentifier']);
        self::assertMatchesRegularExpression('/^[0-9a-f]+$/', $decoded['commitIdentifier']);
    }

    #[Test]
    public function outputIsValidJsonThatCanBeDeserialized(): void
    {
        $fixturesDir = __DIR__.'/../CoverageParser/Fixtures';

        $this->commandTester->execute([
            'coverage-xml-dir' => $fixturesDir,
            '--source-dir' => '',
            '--commit' => 'test123',
        ]);

        $output = $this->commandTester->getDisplay();

        $serializer = new \DaveLiddament\TestSelector\Serializer\TestCoverageReportSerializer();
        $report = $serializer->fromJson($output);

        self::assertSame('test123', $report->commitIdentifier->identifier);
        self::assertCount(3, $report->testCoverages);
    }

    #[Test]
    public function outputOptionWritesToFile(): void
    {
        $fixturesDir = __DIR__.'/../CoverageParser/Fixtures';
        $outputFile = sys_get_temp_dir().'/dits-test-output-'.bin2hex(random_bytes(4)).'.json';

        try {
            $this->commandTester->execute([
                'coverage-xml-dir' => $fixturesDir,
                '--source-dir' => 'src/',
                '--commit' => 'abc123',
                '--output' => $outputFile,
            ]);

            self::assertSame(0, $this->commandTester->getStatusCode());
            self::assertFileExists($outputFile);
            self::assertStringContainsString('TCR written to', $this->commandTester->getDisplay());

            $json = file_get_contents($outputFile);
            self::assertNotFalse($json);

            $serializer = new \DaveLiddament\TestSelector\Serializer\TestCoverageReportSerializer();
            $report = $serializer->fromJson($json);
            self::assertSame('abc123', $report->commitIdentifier->identifier);
        } finally {
            if (file_exists($outputFile)) {
                unlink($outputFile);
            }
        }
    }
}
