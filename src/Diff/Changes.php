<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Diff;

readonly class Changes
{
    public function __construct(
        private Differences $differences,
    ) {
    }

    public function hasChanged(string $fileName, int $lineNumber): bool
    {
        foreach ($this->differences->fileDiffs as $fileDiff) {
            if ($fileDiff->fileName === $fileName) {
                return true;
            }
        }

        foreach ($this->differences->lineDiffs as $lineDiff) {
            if ($lineDiff->fileName === $fileName && $lineDiff->lineNumber === $lineNumber) {
                return true;
            }
        }

        return false;
    }
}
