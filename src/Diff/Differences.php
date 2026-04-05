<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Diff;

readonly class Differences
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
}
