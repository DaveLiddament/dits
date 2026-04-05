<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\DiffFinder;

use DaveLiddament\TestSelector\DiffFinder\GitCommandRunner;
use PHPUnit\Framework\Assert;

final class StrictFakeGitCommandRunner implements GitCommandRunner
{
    /** @var array<string, list<string>> */
    private array $responses = [];

    /**
     * @param list<string> $args
     * @param list<string> $output
     */
    public function addResponse(array $args, array $output): void
    {
        $key = implode(' ', $args);
        $this->responses[$key] = $output;
    }

    public function run(array $args): array
    {
        $key = implode(' ', $args);

        Assert::assertArrayHasKey($key, $this->responses, sprintf('Unexpected git command: %s', $key));

        return $this->responses[$key];
    }
}
