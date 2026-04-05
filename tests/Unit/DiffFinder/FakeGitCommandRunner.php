<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\DiffFinder;

use DaveLiddament\TestSelector\DiffFinder\GitCommandRunner;

final class FakeGitCommandRunner implements GitCommandRunner
{
    /** @var array<string, list<string>> */
    private array $responses = [];

    /**
     * @param list<string> $output
     */
    public function addResponse(string $commandKey, array $output): void
    {
        $this->responses[$commandKey] = $output;
    }

    public function run(array $args): array
    {
        $key = implode(' ', $args);

        foreach ($this->responses as $pattern => $output) {
            if (str_contains($key, $pattern)) {
                return $output;
            }
        }

        return [];
    }
}
