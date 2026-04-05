<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\TestSelector;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\Coverage\LineCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use DaveLiddament\TestSelector\Diff\Changes;
use DaveLiddament\TestSelector\Diff\Differences;
use DaveLiddament\TestSelector\Diff\FileDiff;
use DaveLiddament\TestSelector\Diff\LineDiff;
use DaveLiddament\TestSelector\TestSelector\TestSelector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('006-test-selector')]
final class TestSelectorTest extends TestCase
{
    private TestSelector $selector;

    protected function setUp(): void
    {
        $this->selector = new TestSelector();
    }

    #[Test]
    public function noChangesSelectsNoTests(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(
                new TestName('App\\Tests\\FooTest::testBar'),
                new LineCoverage('src/Foo.php', 10),
            ),
        );
        $changes = new Changes(new Differences([], []));

        $selected = $this->selector->selectTests($report, $changes);

        self::assertSame([], $selected);
    }

    #[Test]
    public function lineLevelChangeSelectsMatchingTest(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(
                new TestName('App\\Tests\\FooTest::testBar'),
                new LineCoverage('src/Foo.php', 10),
                new LineCoverage('src/Foo.php', 11),
            ),
        );
        $changes = new Changes(new Differences(
            [],
            [new LineDiff('src/Foo.php', 10)],
        ));

        $selected = $this->selector->selectTests($report, $changes);

        self::assertCount(1, $selected);
        self::assertSame('App\\Tests\\FooTest::testBar', $selected[0]->testName);
    }

    #[Test]
    public function fileLevelChangeSelectsAllTestsCoveringThatFile(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(
                new TestName('App\\Tests\\FooTest::testA'),
                new LineCoverage('src/Foo.php', 10),
            ),
            new TestCoverage(
                new TestName('App\\Tests\\FooTest::testB'),
                new LineCoverage('src/Foo.php', 20),
            ),
            new TestCoverage(
                new TestName('App\\Tests\\BarTest::testC'),
                new LineCoverage('src/Bar.php', 5),
            ),
        );
        $changes = new Changes(new Differences(
            [new FileDiff('src/Foo.php')],
            [],
        ));

        $selected = $this->selector->selectTests($report, $changes);

        self::assertCount(2, $selected);
        $names = array_map(static fn (TestName $t): string => $t->testName, $selected);
        sort($names);
        self::assertSame([
            'App\\Tests\\FooTest::testA',
            'App\\Tests\\FooTest::testB',
        ], $names);
    }

    #[Test]
    public function multipleChangesSelectMultipleTests(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(
                new TestName('App\\Tests\\FooTest::testA'),
                new LineCoverage('src/Foo.php', 10),
            ),
            new TestCoverage(
                new TestName('App\\Tests\\BarTest::testB'),
                new LineCoverage('src/Bar.php', 5),
            ),
        );
        $changes = new Changes(new Differences(
            [],
            [
                new LineDiff('src/Foo.php', 10),
                new LineDiff('src/Bar.php', 5),
            ],
        ));

        $selected = $this->selector->selectTests($report, $changes);

        self::assertCount(2, $selected);
    }

    #[Test]
    public function noMatchingCoverageSelectsNothing(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(
                new TestName('App\\Tests\\FooTest::testBar'),
                new LineCoverage('src/Foo.php', 10),
            ),
        );
        $changes = new Changes(new Differences(
            [],
            [new LineDiff('src/Other.php', 99)],
        ));

        $selected = $this->selector->selectTests($report, $changes);

        self::assertSame([], $selected);
    }

    #[Test]
    public function testWithNoLineCoveragesIsNotSelected(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(
                new TestName('App\\Tests\\EmptyTest::testNothing'),
            ),
        );
        $changes = new Changes(new Differences(
            [new FileDiff('src/Foo.php')],
            [],
        ));

        $selected = $this->selector->selectTests($report, $changes);

        self::assertSame([], $selected);
    }
}
