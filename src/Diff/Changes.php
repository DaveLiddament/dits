<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Diff;

readonly class Changes
{
    /** @var array<string, true> */
    private array $fileDiffsIndex;

    /** @var array<string, array<int, true>> */
    private array $lineDiffsIndex;

    public function __construct(Differences $differences)
    {
        $fileDiffsIndex = [];
        foreach ($differences->fileDiffs as $fileDiff) {
            $fileDiffsIndex[$fileDiff->fileName] = true;
        }
        $this->fileDiffsIndex = $fileDiffsIndex;

        $lineDiffsIndex = [];
        foreach ($differences->lineDiffs as $lineDiff) {
            $lineDiffsIndex[$lineDiff->fileName][$lineDiff->lineNumber] = true;
        }
        $this->lineDiffsIndex = $lineDiffsIndex;
    }

    public function hasChanged(string $fileName, int $lineNumber): bool
    {
        if (isset($this->fileDiffsIndex[$fileName])) {
            return true;
        }

        if (!isset($this->lineDiffsIndex[$fileName])) {
            return false;
        }

        $lines = $this->lineDiffsIndex[$fileName];

        return isset($lines[$lineNumber - 1]) || isset($lines[$lineNumber]) || isset($lines[$lineNumber + 1]);
    }
}
