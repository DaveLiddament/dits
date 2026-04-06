<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Command;

use DaveLiddament\TestSelector\Merger\TestCoverageReportMerger;
use DaveLiddament\TestSelector\Serializer\TestCoverageReportSerializer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'merge-tcr',
    description: 'Merge multiple JSON Test Coverage Reports into one',
)]
final class MergeTcrCommand extends Command
{
    public function __construct(
        private readonly TestCoverageReportSerializer $serializer = new TestCoverageReportSerializer(),
        private readonly TestCoverageReportMerger $merger = new TestCoverageReportMerger(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'tcr-files',
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Paths to TCR JSON files to merge',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> $files */
        $files = $input->getArgument('tcr-files');

        if (\count($files) < 2) {
            $output->writeln('<error>At least 2 TCR files are required to merge.</error>');

            return Command::FAILURE;
        }

        $reports = [];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                $output->writeln(sprintf('<error>File not found: %s</error>', $file));

                return Command::FAILURE;
            }

            $json = file_get_contents($file);
            if (false === $json) {
                $output->writeln(sprintf('<error>Failed to read file: %s</error>', $file));

                return Command::FAILURE;
            }

            try {
                $reports[] = $this->serializer->fromJson($json);
            } catch (\Throwable $e) {
                $output->writeln(sprintf('<error>Failed to parse %s: %s</error>', $file, $e->getMessage()));

                return Command::FAILURE;
            }
        }

        try {
            $merged = $this->merger->merge($reports);
        } catch (\InvalidArgumentException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln($this->serializer->toJson($merged));

        return Command::SUCCESS;
    }
}
