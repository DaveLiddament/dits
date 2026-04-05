<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\TestSelector;

use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use DaveLiddament\TestSelector\Diff\Changes;

final readonly class TestSelector
{
    /**
     * @return list<TestName>
     */
    public function selectTests(TestCoverageReport $report, Changes $changes): array
    {
        $selectedTests = [];

        foreach ($report->testCoverages as $testCoverage) {
            foreach ($testCoverage->lineCoverages as $lineCoverage) {
                if ($changes->hasChanged($lineCoverage->fileName, $lineCoverage->lineNumber)) {
                    $selectedTests[] = $testCoverage->testName;

                    break;
                }
            }
        }

        return $selectedTests;
    }
}
