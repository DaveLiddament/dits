<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\DiffFinder;

use DaveLiddament\TestSelector\Diff\Differences;
use DaveLiddament\TestSelector\Diff\FileDiff;
use DaveLiddament\TestSelector\Diff\LineDiff;

final readonly class DiffFinder
{
    public function __construct(
        private GitCommandRunner $gitCommandRunner,
    ) {
    }

    public function find(string $branch, bool $includeUnstaged): Differences
    {
        $ref = $includeUnstaged ? $branch : $branch.'..HEAD';

        $nameStatusOutput = $this->gitCommandRunner->run([
            'diff', '--name-status', '--find-renames', $ref,
        ]);

        $fileDiffs = [];
        $lineDiffs = [];

        foreach ($nameStatusOutput as $line) {
            $parsed = $this->parseNameStatusLine($line);

            if (null === $parsed) {
                continue;
            }

            [$status, $fileName] = $parsed;

            if ('M' === $status) {
                $diffOutput = $this->gitCommandRunner->run([
                    'diff', '--unified=0', '--no-color', '--no-ext-diff', $ref, '--', $fileName,
                ]);
                $lineDiffs = [...$lineDiffs, ...$this->parseUnifiedDiff($diffOutput, $fileName)];
            } else {
                $fileDiffs[] = new FileDiff($fileName);
            }
        }

        return new Differences($fileDiffs, $lineDiffs);
    }

    /**
     * @return array{string, string}|null
     */
    private function parseNameStatusLine(string $line): ?array
    {
        $line = trim($line);

        if ('' === $line) {
            return null;
        }

        $parts = preg_split('/\t+/', $line);
        if (false === $parts || \count($parts) < 2) {
            return null;
        }

        $status = $parts[0][0];

        // For renames (R100), use the old file name for coverage matching
        if ('R' === $status) {
            return ['R', $parts[1]];
        }

        return [$status, $parts[1]];
    }

    /**
     * Parses unified diff output to extract changed line numbers in the old file.
     *
     * @param list<string> $diffLines
     *
     * @return list<LineDiff>
     */
    private function parseUnifiedDiff(array $diffLines, string $fileName): array
    {
        $lineDiffs = [];
        $oldLineNumber = null;
        $insertionRecorded = false;

        foreach ($diffLines as $line) {
            if (str_starts_with($line, '@@')) {
                $oldLineNumber = $this->parseHunkHeaderOldStart($line);
                $insertionRecorded = false;

                continue;
            }

            if (null === $oldLineNumber) {
                continue;
            }

            if (str_starts_with($line, '-') && !str_starts_with($line, '---')) {
                $lineDiffs[] = new LineDiff($fileName, $oldLineNumber);
                ++$oldLineNumber;
                $insertionRecorded = false;

                continue;
            }

            if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
                if (!$insertionRecorded) {
                    // Record insertion at the current old line position.
                    // For pure insertion hunks (@@ -N,0 +M,count @@), oldLineNumber is N
                    // (the line after which insertion occurs). Use max(1, ...) for insertions before line 1.
                    $lineDiffs[] = new LineDiff($fileName, max(1, $oldLineNumber));
                    $insertionRecorded = true;
                }

                continue;
            }
        }

        return $lineDiffs;
    }

    private function parseHunkHeaderOldStart(string $line): ?int
    {
        if (1 !== preg_match('/@@ -(\d+)/', $line, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
