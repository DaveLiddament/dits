<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Serializer;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\Coverage\LineCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use Webmozart\Assert\Assert;

final readonly class TestCoverageReportSerializer
{
    public function toJson(TestCoverageReport $report): string
    {
        $data = [
            'commitIdentifier' => $report->commitIdentifier->identifier,
            'testCoverages' => array_map(
                static fn (TestCoverage $tc): array => [
                    'testName' => $tc->testName->testName,
                    'lineCoverages' => array_map(
                        static fn (LineCoverage $lc): array => [
                            'fileName' => $lc->fileName,
                            'lineNumber' => $lc->lineNumber,
                        ],
                        $tc->lineCoverages,
                    ),
                ],
                $report->testCoverages,
            ),
        ];

        return json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    public function fromJson(string $json): TestCoverageReport
    {
        $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        Assert::isArray($data);

        /** @var array<string, mixed> $data */
        Assert::keyExists($data, 'commitIdentifier');
        Assert::keyExists($data, 'testCoverages');
        Assert::string($data['commitIdentifier']);
        Assert::isArray($data['testCoverages']);

        $commitIdentifier = new CommitIdentifier($data['commitIdentifier']);

        $testCoverages = [];

        /** @var array<string, mixed> $tcData */
        foreach ($data['testCoverages'] as $tcData) {
            Assert::keyExists($tcData, 'testName');
            Assert::string($tcData['testName']);
            Assert::keyExists($tcData, 'lineCoverages');
            Assert::isArray($tcData['lineCoverages']);

            $lineCoverages = [];

            /** @var array<string, mixed> $lcData */
            foreach ($tcData['lineCoverages'] as $lcData) {
                Assert::keyExists($lcData, 'fileName');
                Assert::string($lcData['fileName']);
                Assert::keyExists($lcData, 'lineNumber');
                Assert::integer($lcData['lineNumber']);
                $lineCoverages[] = new LineCoverage($lcData['fileName'], $lcData['lineNumber']);
            }

            $testCoverages[] = new TestCoverage(
                new TestName($tcData['testName']),
                ...$lineCoverages,
            );
        }

        return new TestCoverageReport($commitIdentifier, ...$testCoverages);
    }
}
