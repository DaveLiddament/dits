<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Coverage;

use Webmozart\Assert\Assert;

readonly class LineCoverage
{
    public function __construct(
        public string $fileName,
        public int $lineNumber,
    ) {
        Assert::notEmpty($fileName, 'File name must not be empty');
        Assert::positiveInteger($lineNumber, 'Line number must be positive');
    }
}
