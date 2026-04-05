<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Command;

use DaveLiddament\TestSelector\Config\ConfigLoader;
use DaveLiddament\TestSelector\Coverage\TestName;
use DaveLiddament\TestSelector\Diff\Changes;
use DaveLiddament\TestSelector\DiffFinder\DiffFinder;
use DaveLiddament\TestSelector\DiffFinder\GitCommandRunner;
use DaveLiddament\TestSelector\DiffFinder\ProcessGitCommandRunner;
use DaveLiddament\TestSelector\Serializer\TestCoverageReportSerializer;
use DaveLiddament\TestSelector\TestSelector\TestSelector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dits',
    description: 'Select tests to run based on code changes since coverage was collected',
)]
final class DitsCommand extends Command
{
    private const int EXIT_INPUT_ERROR = 1;
    private const int EXIT_GIT_ERROR = 2;

    /**
     * @param string|null $stdinOverride If provided, used instead of reading php://stdin (for testing)
     */
    public function __construct(
        private readonly ?GitCommandRunner $gitCommandRunner = null,
        private readonly TestCoverageReportSerializer $serializer = new TestCoverageReportSerializer(),
        private readonly TestSelector $testSelector = new TestSelector(),
        private readonly ConfigLoader $configLoader = new ConfigLoader(),
        private readonly ?string $stdinOverride = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'include-unstaged',
            'u',
            InputOption::VALUE_NONE,
            'Include uncommitted working tree changes and untracked files',
        );

        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_REQUIRED,
            'Output format: list, phpunit-filter, json',
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

            return self::EXIT_INPUT_ERROR;
        }

        $stdinContent = $this->readStdin();

        if ('' === $stdinContent) {
            $output->writeln('<error>No input received on stdin. Pipe a TCR JSON file.</error>');

            return self::EXIT_INPUT_ERROR;
        }

        try {
            $report = $this->serializer->fromJson($stdinContent);
        } catch (\Throwable) {
            $output->writeln('<error>Failed to parse TCR JSON from stdin.</error>');

            return self::EXIT_INPUT_ERROR;
        }

        // CLI --include-unstaged flag overrides config (flag present = true)
        $includeUnstaged = true === $input->getOption('include-unstaged') || $config->isIncludeUnstaged();

        $commitRef = $report->commitIdentifier->identifier;

        $gitRunner = $this->gitCommandRunner ?? new ProcessGitCommandRunner((string) getcwd());

        try {
            $diffFinder = new DiffFinder($gitRunner);
            $differences = $diffFinder->find($commitRef, $includeUnstaged);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>Git diff failed: %s</error>', $e->getMessage()));

            return self::EXIT_GIT_ERROR;
        }

        $changes = new Changes($differences);
        $selectedTests = $this->testSelector->selectTests($report, $changes);

        // CLI --format overrides config
        /** @var string|null $formatOption */
        $formatOption = $input->getOption('format');
        $format = $formatOption ?? $config->getFormat();

        $this->writeOutput($output, $selectedTests, $format);

        return Command::SUCCESS;
    }

    /**
     * @param list<TestName> $tests
     */
    private function writeOutput(OutputInterface $output, array $tests, string $format): void
    {
        if ([] === $tests) {
            return;
        }

        match ($format) {
            'phpunit-filter' => $output->writeln($this->formatPhpUnitFilter($tests)),
            'json' => $output->writeln($this->formatJson($tests)),
            default => $output->writeln($this->formatList($tests)),
        };
    }

    /**
     * @param list<TestName> $tests
     */
    private function formatList(array $tests): string
    {
        return implode("\n", array_map(
            static fn (TestName $t): string => $t->testName,
            $tests,
        ));
    }

    /**
     * @param list<TestName> $tests
     */
    private function formatPhpUnitFilter(array $tests): string
    {
        $escaped = array_map(
            static fn (TestName $t): string => str_replace('\\', '\\\\', $t->testName),
            $tests,
        );

        return "--filter='".implode('|', $escaped)."'";
    }

    /**
     * @param list<TestName> $tests
     */
    private function formatJson(array $tests): string
    {
        return json_encode(
            array_map(static fn (TestName $t): string => $t->testName, $tests),
            \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES,
        );
    }

    private function readStdin(): string
    {
        if (null !== $this->stdinOverride) {
            return $this->stdinOverride;
        }

        $stdin = file_get_contents('php://stdin');

        return false === $stdin ? '' : trim($stdin);
    }
}
