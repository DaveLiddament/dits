<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Merger;

use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use Webmozart\Assert\Assert;

final readonly class TestCoverageReportMerger
{
    /**
     * @param list<TestCoverageReport> $reports
     */
    public function merge(array $reports): TestCoverageReport
    {
        Assert::minCount($reports, 2, 'At least 2 reports are required to merge');

        $commitIdentifier = $reports[0]->commitIdentifier;

        $allTestCoverages = [];

        foreach ($reports as $report) {
            Assert::same(
                $report->commitIdentifier->identifier,
                $commitIdentifier->identifier,
                sprintf(
                    'All reports must have the same commit identifier. Expected "%s", got "%s".',
                    $commitIdentifier->identifier,
                    $report->commitIdentifier->identifier,
                ),
            );

            $allTestCoverages = [...$allTestCoverages, ...$report->testCoverages];
        }

        return new TestCoverageReport($commitIdentifier, ...$allTestCoverages);
    }
}
