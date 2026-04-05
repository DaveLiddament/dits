<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\DiffFinder;

interface GitCommandRunner
{
    /**
     * Runs a git command and returns the output lines.
     *
     * @param list<string> $args
     *
     * @return list<string>
     */
    public function run(array $args): array;
}
