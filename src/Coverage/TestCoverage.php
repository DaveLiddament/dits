<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Coverage;

readonly class TestCoverage
{
    /** @var list<LineCoverage> */
    public array $lineCoverages;

    public function __construct(
        public TestName $testName,
        LineCoverage ...$lineCoverages,
    ) {
        $this->lineCoverages = array_values($lineCoverages);
    }
}
