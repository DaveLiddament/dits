<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Coverage;

use Webmozart\Assert\Assert;

readonly class TestName
{
    public function __construct(
        public string $testName,
    ) {
        Assert::notEmpty($testName, 'Test name must not be empty');
    }
}
