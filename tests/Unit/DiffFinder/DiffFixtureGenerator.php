<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\DiffFinder;

final class DiffFixtureGenerator
{
    /**
     * Uses `git diff --no-index` to generate a real unified diff between two files,
     * then replaces the filesystem paths with the desired file path.
     *
     * @return list<string>
     */
    public static function generate(string $beforePath, string $afterPath, string $filePath): array
    {
        $command = sprintf(
            'git diff --no-index --unified=0 --no-color --no-ext-diff %s %s',
            escapeshellarg($beforePath),
            escapeshellarg($afterPath),
        );

        exec($command, $output); // @phpstan-ignore disallowed.function

        $raw = implode("\n", $output);

        if ('/dev/null' !== $beforePath) {
            $raw = str_replace(ltrim($beforePath, '/'), $filePath, $raw);
        }
        if ('/dev/null' !== $afterPath) {
            $raw = str_replace(ltrim($afterPath, '/'), $filePath, $raw);
        }

        return explode("\n", $raw);
    }
}
