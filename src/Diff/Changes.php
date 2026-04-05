<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Diff;

readonly class Changes
{
    /** @var list<FileDiff> */
    public array $fileDiffs;

    /** @var list<LineDiff> */
    public array $lineDiffs;

    /**
     * @param array<FileDiff> $fileDiffs
     * @param array<LineDiff> $lineDiffs
     */
    public function __construct(
        array $fileDiffs,
        array $lineDiffs,
    ) {
        $this->fileDiffs = array_values($fileDiffs);
        $this->lineDiffs = array_values($lineDiffs);
    }

    public function hasChanged(string $fileName, int $lineNumber): bool
    {
        foreach ($this->fileDiffs as $fileDiff) {
            if ($fileDiff->fileName === $fileName) {
                return true;
            }
        }

        foreach ($this->lineDiffs as $lineDiff) {
            if ($lineDiff->fileName === $fileName && $lineDiff->lineNumber === $lineNumber) {
                return true;
            }
        }

        return false;
    }
}
