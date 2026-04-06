<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Command;

use DaveLiddament\TestSelector\Config\ConfigLoader;
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
        private readonly ConfigLoader $configLoader = new ConfigLoader(),
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
        );

        $this->addOption(
            'commit',
            'c',
            InputOption::VALUE_REQUIRED,
            'Commit SHA to record (defaults to git rev-parse HEAD)',
        );

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Write TCR to file instead of stdout',
        );

        $this->addOption(
            'config',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to config file (defaults to .dits.php in project root)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $configPath */
        $configPath = $input->getOption('config');

        try {
            $config = $this->configLoader->load($configPath);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        /** @var string $coverageXmlDir */
        $coverageXmlDir = $input->getArgument('coverage-xml-dir');

        /** @var string|null $sourceDirOption */
        $sourceDirOption = $input->getOption('source-dir');
        $sourceDir = $sourceDirOption ?? $config->getSourceDir();

        /** @var string|null $commitOption */
        $commitOption = $input->getOption('commit');
        $commitSha = $commitOption ?? $config->getCommit() ?? $this->resolveCommitFromGit();

        $commitIdentifier = new CommitIdentifier($commitSha);
        $report = $this->parser->parse($coverageXmlDir, $commitIdentifier, $sourceDir);
        $json = $this->serializer->toJson($report);

        /** @var string|null $outputOption */
        $outputOption = $input->getOption('output');
        $outputPath = $outputOption ?? $config->getOutput();

        if (null !== $outputPath) {
            $result = file_put_contents($outputPath, $json."\n");
            if (false === $result) {
                $output->writeln(sprintf('<error>Failed to write to file: %s</error>', $outputPath));

                return Command::FAILURE;
            }

            $output->writeln(sprintf('TCR written to %s', $outputPath));
        } else {
            $output->writeln($json);
        }

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
