<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\DiffFinder;

use Webmozart\Assert\Assert;

final readonly class ProcessGitCommandRunner implements GitCommandRunner
{
    public function __construct(
        private string $projectRoot,
    ) {
        Assert::directory($projectRoot, 'Project root must be a directory');
    }

    public function run(array $args): array
    {
        $command = sprintf(
            'cd %s && git %s 2>&1',
            escapeshellarg($this->projectRoot),
            implode(' ', array_map(escapeshellarg(...), $args)),
        );

        exec($command, $output, $exitCode); // @phpstan-ignore disallowed.function

        Assert::same($exitCode, 0, sprintf("git command failed (exit %d):\n%s", $exitCode, implode("\n", $output)));

        return $output;
    }
}
