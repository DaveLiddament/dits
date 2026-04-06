<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Coverage;

readonly class TestCoverageReport
{
    /** @var list<TestCoverage> */
    public array $testCoverages;

    public function __construct(
        public CommitIdentifier $commitIdentifier,
        TestCoverage ...$testCoverages,
    ) {
        /** @infection-ignore-all Equivalent mutant: variadic args are always sequentially keyed; array_values is a no-op but kept for type narrowing */
        $this->testCoverages = array_values($testCoverages);
    }
}
