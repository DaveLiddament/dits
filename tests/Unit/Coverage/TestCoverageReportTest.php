<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Coverage;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\Coverage\LineCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('001-coverage-report')]
final class TestCoverageReportTest extends TestCase
{
    #[Test]
    public function validConstruction(): void
    {
        $commitIdentifier = new CommitIdentifier('abc123');
        $testCoverage1 = new TestCoverage(
            new TestName('App\\Tests\\FooTest::testBar'),
            new LineCoverage('src/Foo.php', 10),
        );
        $testCoverage2 = new TestCoverage(
            new TestName('App\\Tests\\BazTest::testQux'),
            new LineCoverage('src/Baz.php', 5),
        );

        $report = new TestCoverageReport($commitIdentifier, $testCoverage1, $testCoverage2);

        self::assertSame($commitIdentifier, $report->commitIdentifier);
        self::assertCount(2, $report->testCoverages);
        self::assertSame($testCoverage1, $report->testCoverages[0]);
        self::assertSame($testCoverage2, $report->testCoverages[1]);
    }

    #[Test]
    public function validConstructionWithNoTestCoverages(): void
    {
        $commitIdentifier = new CommitIdentifier('abc123');

        $report = new TestCoverageReport($commitIdentifier);

        self::assertSame([], $report->testCoverages);
    }
}
