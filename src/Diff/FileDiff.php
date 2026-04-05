<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Diff;

use Webmozart\Assert\Assert;

readonly class FileDiff
{
    public function __construct(
        public string $fileName,
    ) {
        Assert::notEmpty($fileName, 'File name must not be empty');
    }
}
