<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Command;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\CoverageParser\PhpUnitCoverageXmlParser;
use DaveLiddament\TestSelector\Serializer\TestCoverageReportSerializer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'generate-tcr',
    description: 'Generate a JSON Test Coverage Report from PHPUnit coverage XML',
)]
final class GenerateTcrCommand extends Command
{
    public function __construct(
        private readonly PhpUnitCoverageXmlParser $parser = new PhpUnitCoverageXmlParser(),
        private readonly TestCoverageReportSerializer $serializer = new TestCoverageReportSerializer(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'coverage-xml-dir',
            InputArgument::REQUIRED,
            'Path to the PHPUnit --coverage-xml output directory',
        );

        $this->addOption(
            'source-dir',
            's',
            InputOption::VALUE_REQUIRED,
            'Source directory relative to project root',
            'src/',
        );

        $this->addOption(
            'commit',
            'c',
            InputOption::VALUE_REQUIRED,
            'Commit SHA to record (defaults to git rev-parse HEAD)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $coverageXmlDir */
        $coverageXmlDir = $input->getArgument('coverage-xml-dir');

        /** @var string $sourceDir */
        $sourceDir = $input->getOption('source-dir');

        /** @var string|null $commitOption */
        $commitOption = $input->getOption('commit');

        $commitSha = $commitOption ?? $this->resolveCommitFromGit();

        $commitIdentifier = new CommitIdentifier($commitSha);
        $report = $this->parser->parse($coverageXmlDir, $commitIdentifier, $sourceDir);
        $json = $this->serializer->toJson($report);

        $output->writeln($json);

        return Command::SUCCESS;
    }

    private function resolveCommitFromGit(): string
    {
        $sha = trim((string) shell_exec('git rev-parse HEAD')); // @phpstan-ignore disallowed.function

        if ('' === $sha) {
            throw new \RuntimeException('Failed to resolve commit SHA via git rev-parse HEAD');
        }

        return $sha;
    }
}
