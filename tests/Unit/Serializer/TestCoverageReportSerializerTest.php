<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Serializer;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\Coverage\LineCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use DaveLiddament\TestSelector\Serializer\TestCoverageReportSerializer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('007-generate-tcr-command')]
final class TestCoverageReportSerializerTest extends TestCase
{
    private TestCoverageReportSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new TestCoverageReportSerializer();
    }

    #[Test]
    public function roundTripSerializationPreservesData(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123def'),
            new TestCoverage(
                new TestName('App\\Tests\\FooTest::testBar'),
                new LineCoverage('src/Foo.php', 10),
                new LineCoverage('src/Foo.php', 11),
            ),
            new TestCoverage(
                new TestName('App\\Tests\\BazTest::testQux'),
                new LineCoverage('src/Baz.php', 5),
            ),
        );

        $json = $this->serializer->toJson($report);
        $deserialized = $this->serializer->fromJson($json);

        self::assertSame('abc123def', $deserialized->commitIdentifier->identifier);
        self::assertCount(2, $deserialized->testCoverages);

        self::assertSame('App\\Tests\\FooTest::testBar', $deserialized->testCoverages[0]->testName->testName);
        self::assertCount(2, $deserialized->testCoverages[0]->lineCoverages);
        self::assertSame('src/Foo.php', $deserialized->testCoverages[0]->lineCoverages[0]->fileName);
        self::assertSame(10, $deserialized->testCoverages[0]->lineCoverages[0]->lineNumber);
        self::assertSame(11, $deserialized->testCoverages[0]->lineCoverages[1]->lineNumber);

        self::assertSame('App\\Tests\\BazTest::testQux', $deserialized->testCoverages[1]->testName->testName);
        self::assertCount(1, $deserialized->testCoverages[1]->lineCoverages);
        self::assertSame('src/Baz.php', $deserialized->testCoverages[1]->lineCoverages[0]->fileName);
        self::assertSame(5, $deserialized->testCoverages[1]->lineCoverages[0]->lineNumber);
    }

    #[Test]
    public function toJsonProducesValidJson(): void
    {
        $report = new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(
                new TestName('App\\Tests\\FooTest::testBar'),
                new LineCoverage('src/Foo.php', 10),
            ),
        );

        $json = $this->serializer->toJson($report);
        $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertSame('abc123', $decoded['commitIdentifier']);
        self::assertCount(1, $decoded['testCoverages']);
        self::assertSame('App\\Tests\\FooTest::testBar', $decoded['testCoverages'][0]['testName']);
    }

    #[Test]
    public function emptyReportRoundTrips(): void
    {
        $report = new TestCoverageReport(new CommitIdentifier('abc123'));

        $json = $this->serializer->toJson($report);
        $deserialized = $this->serializer->fromJson($json);

        self::assertSame('abc123', $deserialized->commitIdentifier->identifier);
        self::assertCount(0, $deserialized->testCoverages);
    }
}
