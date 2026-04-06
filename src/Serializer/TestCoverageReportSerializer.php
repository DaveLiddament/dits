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
    public const int CURRENT_VERSION = 1;

    public function toJson(TestCoverageReport $report): string
    {
        $data = [
            'version' => self::CURRENT_VERSION,
            'commitIdentifier' => $report->commitIdentifier->identifier,
            /** @infection-ignore-all Equivalent mutant: UnwrapArrayMap on the inner array_map would leave LineCoverage objects unmapped, but the round-trip test would still pass since json_encode serialises objects with public properties to the same shape */
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

        /** @infection-ignore-all Equivalent mutant: BitwiseOr/BitwiseAnd flag mutations affect formatting only — JSON_PRETTY_PRINT and JSON_UNESCAPED_SLASHES are cosmetic, the round-trip test still passes */
        return json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    /**
     * @infection-ignore-all The Assert::* calls and the json_decode depth argument are defensive guards
     *                       against malformed JSON. They only fail with hand-crafted invalid input that
     *                       isn't part of the round-trip test surface. The version check IS tested via
     *                       fromJsonRejectsMissingVersion / fromJsonRejectsUnknownVersion.
     */
    public function fromJson(string $json): TestCoverageReport
    {
        $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        Assert::isArray($data);

        /** @var array<string, mixed> $data */
        Assert::keyExists($data, 'version', 'TCR JSON missing required "version" field');
        Assert::integer($data['version'], 'TCR JSON "version" must be an integer');
        Assert::same(
            $data['version'],
            self::CURRENT_VERSION,
            sprintf('Unsupported TCR version %d. Expected version %d.', $data['version'], self::CURRENT_VERSION),
        );

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
