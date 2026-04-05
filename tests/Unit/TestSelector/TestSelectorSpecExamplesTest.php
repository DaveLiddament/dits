<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\TestSelector;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\Coverage\LineCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use DaveLiddament\TestSelector\Diff\Changes;
use DaveLiddament\TestSelector\DiffFinder\DiffFinder;
use DaveLiddament\TestSelector\Tests\Unit\DiffFinder\DiffFixtureGenerator;
use DaveLiddament\TestSelector\Tests\Unit\DiffFinder\FakeGitCommandRunner;
use DaveLiddament\TestSelector\TestSelector\TestSelector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end tests matching the examples in specs/006-test-selector.md.
 *
 * Original code coverage:
 *   Test 1 executes lines: 2, 3, 4, 5, 6, 10
 *   Test 2 executes lines: 2, 3, 8, 10
 */
#[Ticket('006-test-selector')]
final class TestSelectorSpecExamplesTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/Fixtures';
    private const string FILE = 'src/HandlePerson.php';

    #[Test]
    public function example1SingleLineUpdated(): void
    {
        // Line 3 changed: isManager() → isSupervisor()
        // LineDiff at line 3 → Test 1 (covers 3) and Test 2 (covers 3)
        $this->assertSelectedTests('example1.php', ['Test1', 'Test2']);
    }

    #[Test]
    public function example2SingleLineRemoved(): void
    {
        // Line 6 removed: $person->setSalary($salary)
        // LineDiff at line 6 → Test 1 (covers 6). Fuzz to 5 and 7: Test 1 covers 5, no test covers 7.
        $this->assertSelectedTests('example2.php', ['Test1']);
    }

    #[Test]
    public function example3SingleLineAdded(): void
    {
        // Insertion between lines 7 and 8: $person->blankEmail()
        // Insertion recorded at line 7 → fuzz checks 6, 7, 8: Test 1 covers 6, Test 2 covers 8
        $this->assertSelectedTests('example3.php', ['Test1', 'Test2']);
    }

    #[Test]
    public function example4MultipleLinesAdded(): void
    {
        // Multiple insertions between lines 7 and 8
        // Same as example 3: insertion at line 7 → fuzz catches Test 1 (line 6) and Test 2 (line 8)
        $this->assertSelectedTests('example4.php', ['Test1', 'Test2']);
    }

    #[Test]
    public function example5LineAddedAtStart(): void
    {
        // Insertion between lines 1 and 2: $name = strtolower($name)
        // Insertion recorded at line 1 → fuzz checks lines 1 and 2: Test 1 covers 2, Test 2 covers 2
        $this->assertSelectedTests('example5.php', ['Test1', 'Test2']);
    }

    #[Test]
    public function example6LineAddedAtStartAndEnd(): void
    {
        // Insertion at start (line 1) + insertion at end (line 10)
        // Line 1 fuzz → line 2 (both tests). Line 10 → both tests cover 10.
        $this->assertSelectedTests('example6.php', ['Test1', 'Test2']);
    }

    #[Test]
    public function example7StatementRemoved(): void
    {
        // Insertion at start (line 1) + removal of line 7 (} else {)
        // Line 1 fuzz → line 2 (both tests). Line 7 fuzz → line 6 (Test 1), line 8 (Test 2).
        $this->assertSelectedTests('example7.php', ['Test1', 'Test2']);
    }

    /**
     * @param list<string> $expectedTestNames
     */
    private function assertSelectedTests(string $afterFixture, array $expectedTestNames): void
    {
        $report = $this->buildCoverageReport();
        $differences = $this->buildDifferences($afterFixture);
        $changes = new Changes($differences);

        $selector = new TestSelector();
        $selected = $selector->selectTests($report, $changes);

        $selectedNames = array_map(static fn (TestName $t): string => $t->testName, $selected);
        sort($selectedNames);
        sort($expectedTestNames);

        self::assertSame($expectedTestNames, $selectedNames);
    }

    private function buildCoverageReport(): TestCoverageReport
    {
        return new TestCoverageReport(
            new CommitIdentifier('abc123'),
            new TestCoverage(
                new TestName('Test1'),
                new LineCoverage(self::FILE, 2),
                new LineCoverage(self::FILE, 3),
                new LineCoverage(self::FILE, 4),
                new LineCoverage(self::FILE, 5),
                new LineCoverage(self::FILE, 6),
                new LineCoverage(self::FILE, 10),
            ),
            new TestCoverage(
                new TestName('Test2'),
                new LineCoverage(self::FILE, 2),
                new LineCoverage(self::FILE, 3),
                new LineCoverage(self::FILE, 8),
                new LineCoverage(self::FILE, 10),
            ),
        );
    }

    private function buildDifferences(string $afterFixture): \DaveLiddament\TestSelector\Diff\Differences
    {
        $beforePath = self::FIXTURES.'/before.php';
        $afterPath = self::FIXTURES.'/'.$afterFixture;

        $diffOutput = DiffFixtureGenerator::generate($beforePath, $afterPath, self::FILE);

        $gitRunner = new FakeGitCommandRunner();
        $gitRunner->addResponse('--name-status', ["M\t".self::FILE]);
        $gitRunner->addResponse('-- '.self::FILE, $diffOutput);

        $diffFinder = new DiffFinder($gitRunner);

        return $diffFinder->find('main', true);
    }
}
