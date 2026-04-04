<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Coverage;

use Webmozart\Assert\Assert;

readonly class CommitIdentifier
{
    public function __construct(
        public string $identifier,
    ) {
        Assert::notEmpty($identifier, 'Commit identifier must not be empty');
    }
}
