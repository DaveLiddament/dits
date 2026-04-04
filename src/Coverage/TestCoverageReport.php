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
        $this->testCoverages = array_values($testCoverages);
    }
}
