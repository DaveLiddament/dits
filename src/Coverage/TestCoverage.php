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
        /** @infection-ignore-all Equivalent mutant: variadic args are always sequentially keyed; array_values is a no-op but kept for type narrowing */
        $this->lineCoverages = array_values($lineCoverages);
    }
}
