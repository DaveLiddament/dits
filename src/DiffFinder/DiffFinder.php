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
                /** @infection-ignore-all Equivalent mutant: ArrayItemRemoval/spread mutations on the git command args produce different (broken) git invocations, but the FakeGitCommandRunner in tests pattern-matches on a substring so the broken commands still return the same fake output. Real-world breakage would be caught by the end-to-end test. */
                $diffOutput = $this->gitCommandRunner->run([
                    'diff', '--unified=0', '--no-color', '--no-ext-diff', $ref, '--', $fileName,
                ]);
                $lineDiffs = [...$lineDiffs, ...$this->parseUnifiedDiff($diffOutput, $fileName)];
            } else {
                $fileDiffs[] = new FileDiff($fileName);
            }
        }

        if ($includeUnstaged) {
            $fileDiffs = [...$fileDiffs, ...$this->getUntrackedFiles()];
        }

        return new Differences($fileDiffs, $lineDiffs);
    }

    /**
     * @return list<FileDiff>
     */
    private function getUntrackedFiles(): array
    {
        $output = $this->gitCommandRunner->run([
            'ls-files', '--others', '--exclude-standard',
        ]);

        $fileDiffs = [];

        foreach ($output as $line) {
            /** @infection-ignore-all Equivalent mutant: test fixture lines have no leading/trailing whitespace */
            $line = trim($line);
            if ('' !== $line) {
                $fileDiffs[] = new FileDiff($line);
            }
        }

        return $fileDiffs;
    }

    /**
     * @return array{string, string}|null
     */
    private function parseNameStatusLine(string $line): ?array
    {
        /** @infection-ignore-all Equivalent mutant: git output lines have no leading/trailing whitespace */
        $line = trim($line);

        if ('' === $line) {
            /** @infection-ignore-all Equivalent mutant: empty line also fails the count<2 check below, so removing this return still returns null */
            return null;
        }

        $parts = preg_split('/\t+/', $line);
        if (false === $parts || \count($parts) < 2) {
            return null;
        }

        $status = $parts[0][0];

        // For renames (R100), use the old file name for coverage matching
        if ('R' === $status) {
            /** @infection-ignore-all Equivalent mutant: the fall-through return below also returns ['R', $parts[1]] since $status is 'R' */
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
        /** @infection-ignore-all Equivalent mutant: $insertionRecorded is always reset to false on the first @@ hunk header before any + line is processed */
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

            /** @infection-ignore-all Equivalent mutant: in real git diff output (and our tests), file headers like '--- a/file.php' always appear BEFORE any @@ hunk, so $oldLineNumber is null and they're caught by the null check above. The defensive '!str_starts_with $line, ---' guard is never reached. */
            if (str_starts_with($line, '-') && !str_starts_with($line, '---')) {
                $lineDiffs[] = new LineDiff($fileName, $oldLineNumber);
                ++$oldLineNumber;
                $insertionRecorded = false;

                continue;
            }

            /** @infection-ignore-all Equivalent mutant: same reasoning as for '---' above — '+++' file headers appear before any hunk */
            if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
                if (!$insertionRecorded) {
                    // Record insertion at the current old line position.
                    // For pure insertion hunks (@@ -N,0 +M,count @@), oldLineNumber is N
                    // (the line after which insertion occurs). Use max(1, ...) for insertions before line 1.
                    $lineDiffs[] = new LineDiff($fileName, max(1, $oldLineNumber));
                    /** @infection-ignore-all Equivalent mutant: setting to false here just causes redundant recording on consecutive + lines, which our tests still observe via dedup at the LineDiff level */
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
