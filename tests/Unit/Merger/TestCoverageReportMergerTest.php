<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Merger;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\Coverage\LineCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use DaveLiddament\TestSelector\Merger\TestCoverageReportMerger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('008-merge-tcr-command')]
final class TestCoverageReportMergerTest extends TestCase
{
    private TestCoverageReportMerger $merger;

    protected function setUp(): void
    {
        $this->merger = new TestCoverageReportMerger();
    }

    #[Test]
    public function mergesTwoReports(): void
    {
        $commit = new CommitIdentifier('abc123');

        $report1 = new TestCoverageReport(
            $commit,
            new TestCoverage(new TestName('Test1'), new LineCoverage('src/A.php', 10)),
        );
        $report2 = new TestCoverageReport(
            $commit,
            new TestCoverage(new TestName('Test2'), new LineCoverage('src/B.php', 20)),
        );

        $merged = $this->merger->merge([$report1, $report2]);

        self::assertSame('abc123', $merged->commitIdentifier->identifier);
        self::assertCount(2, $merged->testCoverages);
        self::assertSame('Test1', $merged->testCoverages[0]->testName->testName);
        self::assertSame('Test2', $merged->testCoverages[1]->testName->testName);
    }

    #[Test]
    public function mergesThreeReports(): void
    {
        $commit = new CommitIdentifier('abc123');

        $report1 = new TestCoverageReport($commit, new TestCoverage(new TestName('T1'), new LineCoverage('src/A.php', 1)));
        $report2 = new TestCoverageReport($commit, new TestCoverage(new TestName('T2'), new LineCoverage('src/B.php', 2)));
        $report3 = new TestCoverageReport($commit, new TestCoverage(new TestName('T3'), new LineCoverage('src/C.php', 3)));

        $merged = $this->merger->merge([$report1, $report2, $report3]);

        self::assertCount(3, $merged->testCoverages);
    }

    #[Test]
    public function throwsWhenCommitIdentifiersDiffer(): void
    {
        $report1 = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(new TestName('Test1'), new LineCoverage('src/A.php', 10)),
        );
        $report2 = new TestCoverageReport(
            new CommitIdentifier('def456'),
            new TestCoverage(new TestName('Test2'), new LineCoverage('src/B.php', 20)),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected "abc123", got "def456"');

        $this->merger->merge([$report1, $report2]);
    }

    #[Test]
    public function throwsWhenFewerThanTwoReports(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(new TestName('Test1'), new LineCoverage('src/A.php', 10)),
        );

        $this->expectException(\InvalidArgumentException::class);

        $this->merger->merge([$report]);
    }
}
