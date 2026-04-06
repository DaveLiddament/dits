<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\TestSelector;

use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use DaveLiddament\TestSelector\Diff\Differences;

final readonly class TestSelector
{
    /**
     * @return list<TestName>
     */
    public function selectTests(TestCoverageReport $report, Differences $differences): array
    {
        // Build inverse indexes from the coverage report (one pass).
        // fileToTests:     fileName              -> [testName => TestName]
        // fileLineToTests: fileName -> lineNumber -> [testName => TestName]
        /** @var array<string, array<string, TestName>> $fileToTests */
        $fileToTests = [];
        /** @var array<string, array<int, array<string, TestName>>> $fileLineToTests */
        $fileLineToTests = [];

        foreach ($report->testCoverages as $testCoverage) {
            $testName = $testCoverage->testName;
            $key = $testName->testName;

            foreach ($testCoverage->lineCoverages as $lineCoverage) {
                $fileToTests[$lineCoverage->fileName][$key] = $testName;
                $fileLineToTests[$lineCoverage->fileName][$lineCoverage->lineNumber][$key] = $testName;
            }
        }

        /** @var array<string, TestName> $selected */
        $selected = [];

        // File-level diffs: select all tests covering any line in the file
        foreach ($differences->fileDiffs as $fileDiff) {
            if (isset($fileToTests[$fileDiff->fileName])) {
                foreach ($fileToTests[$fileDiff->fileName] as $key => $testName) {
                    $selected[$key] = $testName;
                }
            }
        }

        // Line-level diffs with ±1 fuzz: select tests covering line, line-1, line+1
        foreach ($differences->lineDiffs as $lineDiff) {
            if (!isset($fileLineToTests[$lineDiff->fileName])) {
                continue;
            }

            $lineMap = $fileLineToTests[$lineDiff->fileName];

            foreach ([$lineDiff->lineNumber - 1, $lineDiff->lineNumber, $lineDiff->lineNumber + 1] as $line) {
                if (isset($lineMap[$line])) {
                    foreach ($lineMap[$line] as $key => $testName) {
                        $selected[$key] = $testName;
                    }
                }
            }
        }

        return array_values($selected);
    }
}
