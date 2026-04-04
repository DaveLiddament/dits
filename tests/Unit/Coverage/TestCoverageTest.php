<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Coverage;

use DaveLiddament\TestSelector\Coverage\LineCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverage;
use DaveLiddament\TestSelector\Coverage\TestName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('001-coverage-report')]
final class TestCoverageTest extends TestCase
{
    #[Test]
    public function validConstruction(): void
    {
        $testName = new TestName('App\\Tests\\FooTest::testBar');
        $line1 = new LineCoverage('src/Foo.php', 10);
        $line2 = new LineCoverage('src/Bar.php', 25);

        $testCoverage = new TestCoverage($testName, $line1, $line2);

        self::assertSame($testName, $testCoverage->testName);
        self::assertCount(2, $testCoverage->lineCoverages);
        self::assertSame($line1, $testCoverage->lineCoverages[0]);
        self::assertSame($line2, $testCoverage->lineCoverages[1]);
    }

    #[Test]
    public function validConstructionWithNoLineCoverages(): void
    {
        $testCoverage = new TestCoverage(new TestName('App\\Tests\\FooTest::testBar'));

        self::assertSame([], $testCoverage->lineCoverages);
    }
}
