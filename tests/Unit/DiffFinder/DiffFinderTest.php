<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\DiffFinder;

use DaveLiddament\TestSelector\DiffFinder\DiffFinder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('005-diff-finder')]
final class DiffFinderTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/Fixtures';

    private FakeGitCommandRunner $gitRunner;
    private DiffFinder $diffFinder;

    protected function setUp(): void
    {
        $this->gitRunner = new FakeGitCommandRunner();
        $this->diffFinder = new DiffFinder($this->gitRunner);
    }

    #[Test]
    public function newFileProducesFileDiff(): void
    {
        $this->gitRunner->addResponse('--name-status', ["A\tsrc/NewClass.php"]);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(1, $differences->fileDiffs);
        self::assertSame('src/NewClass.php', $differences->fileDiffs[0]->fileName);
        self::assertCount(0, $differences->lineDiffs);
    }

    #[Test]
    public function deletedFileProducesFileDiff(): void
    {
        $this->gitRunner->addResponse('--name-status', ["D\tsrc/OldClass.php"]);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(1, $differences->fileDiffs);
        self::assertSame('src/OldClass.php', $differences->fileDiffs[0]->fileName);
        self::assertCount(0, $differences->lineDiffs);
    }

    #[Test]
    public function renamedFileProducesFileDiffWithOldName(): void
    {
        $this->gitRunner->addResponse('--name-status', ["R100\tsrc/Old.php\tsrc/New.php"]);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(1, $differences->fileDiffs);
        self::assertSame('src/Old.php', $differences->fileDiffs[0]->fileName);
        self::assertCount(0, $differences->lineDiffs);
    }

    #[Test]
    public function modifiedFileProducesLineDiffs(): void
    {
        $diffOutput = DiffFixtureGenerator::generate(
            self::FIXTURES.'/ModifiedFile/before.php',
            self::FIXTURES.'/ModifiedFile/after.php',
            'src/Foo.php',
        );

        $this->gitRunner->addResponse('--name-status', ["M\tsrc/Foo.php"]);
        $this->gitRunner->addResponse('-- src/Foo.php', $diffOutput);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(0, $differences->fileDiffs);
        // Line 11 removed (the old `return 1;`) + insertion recorded at line 12 (the new `return 2;`)
        self::assertCount(2, $differences->lineDiffs);
        self::assertSame(11, $differences->lineDiffs[0]->lineNumber);
        self::assertSame(12, $differences->lineDiffs[1]->lineNumber);
    }

    #[Test]
    public function modifiedFileWithMultipleHunksProducesMultipleLineDiffs(): void
    {
        $diffOutput = DiffFixtureGenerator::generate(
            self::FIXTURES.'/MultipleChanges/before.php',
            self::FIXTURES.'/MultipleChanges/after.php',
            'src/Multi.php',
        );

        $this->gitRunner->addResponse('--name-status', ["M\tsrc/Multi.php"]);
        $this->gitRunner->addResponse('-- src/Multi.php', $diffOutput);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(0, $differences->fileDiffs);

        $lineNumbers = array_map(
            static fn ($ld): int => $ld->lineNumber,
            $differences->lineDiffs,
        );
        sort($lineNumbers);

        // Line 11: removal + insertion, Line 21: removal + insertion
        self::assertSame([11, 12, 21, 22], $lineNumbers);
    }

    #[Test]
    public function mixOfFileAndLineChanges(): void
    {
        $diffOutput = DiffFixtureGenerator::generate(
            self::FIXTURES.'/ModifiedFile/before.php',
            self::FIXTURES.'/ModifiedFile/after.php',
            'src/Foo.php',
        );

        $this->gitRunner->addResponse('--name-status', [
            "A\tsrc/NewClass.php",
            "D\tsrc/OldClass.php",
            "M\tsrc/Foo.php",
        ]);
        $this->gitRunner->addResponse('-- src/Foo.php', $diffOutput);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(2, $differences->fileDiffs);
        $fileNames = array_map(static fn ($fd): string => $fd->fileName, $differences->fileDiffs);
        sort($fileNames);
        self::assertSame(['src/NewClass.php', 'src/OldClass.php'], $fileNames);

        self::assertCount(2, $differences->lineDiffs);
        self::assertSame('src/Foo.php', $differences->lineDiffs[0]->fileName);
        self::assertSame('src/Foo.php', $differences->lineDiffs[1]->fileName);
    }

    #[Test]
    public function noChangesProducesEmptyDifferences(): void
    {
        $this->gitRunner->addResponse('--name-status', []);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(0, $differences->fileDiffs);
        self::assertCount(0, $differences->lineDiffs);
    }

    #[Test]
    public function includeUnstagedTrueUsesDirectBranchRefAndChecksUntrackedFiles(): void
    {
        $runner = new StrictFakeGitCommandRunner();
        $runner->addResponse(['diff', '--name-status', '--find-renames', 'main'], ["A\tsrc/Foo.php"]);
        $runner->addResponse(['ls-files', '--others', '--exclude-standard'], ['src/Untracked.php']);

        $diffFinder = new DiffFinder($runner);
        $differences = $diffFinder->find('main', true);

        self::assertCount(2, $differences->fileDiffs);
        $fileNames = array_map(static fn ($fd): string => $fd->fileName, $differences->fileDiffs);
        sort($fileNames);
        self::assertSame(['src/Foo.php', 'src/Untracked.php'], $fileNames);
    }

    #[Test]
    public function includeUnstagedFalseDoesNotCheckUntrackedFiles(): void
    {
        $runner = new StrictFakeGitCommandRunner();
        $runner->addResponse(['diff', '--name-status', '--find-renames', 'main..HEAD'], ["A\tsrc/Foo.php"]);

        $diffFinder = new DiffFinder($runner);
        $differences = $diffFinder->find('main', false);

        self::assertCount(1, $differences->fileDiffs);
    }

    #[Test]
    public function untrackedFilesAreFileDiffs(): void
    {
        $this->gitRunner->addResponse('--name-status', []);
        $this->gitRunner->addResponse('ls-files', [
            'src/NewFile.php',
            'src/AnotherNew.php',
        ]);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(2, $differences->fileDiffs);
        $fileNames = array_map(static fn ($fd): string => $fd->fileName, $differences->fileDiffs);
        sort($fileNames);
        self::assertSame(['src/AnotherNew.php', 'src/NewFile.php'], $fileNames);
    }

    #[Test]
    public function emptyUntrackedFilesListProducesNoDiffs(): void
    {
        $this->gitRunner->addResponse('--name-status', []);
        $this->gitRunner->addResponse('ls-files', []);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(0, $differences->fileDiffs);
    }

    #[Test]
    public function emptyLinesInNameStatusAreSkipped(): void
    {
        $this->gitRunner->addResponse('--name-status', [
            "A\tsrc/Foo.php",
            '',
            "D\tsrc/Bar.php",
        ]);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(2, $differences->fileDiffs);
    }

    #[Test]
    public function malformedNameStatusLinesAreSkipped(): void
    {
        $this->gitRunner->addResponse('--name-status', [
            'not-a-valid-line',
            "A\tsrc/Foo.php",
        ]);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(1, $differences->fileDiffs);
    }

    #[Test]
    public function malformedHunkHeaderSkipsRemovedLines(): void
    {
        $this->gitRunner->addResponse('--name-status', ["M\tsrc/Foo.php"]);
        $this->gitRunner->addResponse('-- src/Foo.php', [
            'diff --git a/src/Foo.php b/src/Foo.php',
            '--- a/src/Foo.php',
            '+++ b/src/Foo.php',
            '@@ malformed @@',
            '-old line',
        ]);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(0, $differences->fileDiffs);
        self::assertCount(0, $differences->lineDiffs);
    }

    #[Test]
    public function fileHeaderLinesAreNotTreatedAsDiffs(): void
    {
        // The "--- a/file.php" and "+++ b/file.php" lines start with - and +
        // but they are file headers, NOT removed/added lines. They must be ignored.
        $this->gitRunner->addResponse('--name-status', ["M\tsrc/Foo.php"]);
        $this->gitRunner->addResponse('-- src/Foo.php', [
            'diff --git a/src/Foo.php b/src/Foo.php',
            '--- a/src/Foo.php',
            '+++ b/src/Foo.php',
            '@@ -10,1 +10,1 @@',
            '-removed line',
            '+added line',
        ]);

        $differences = $this->diffFinder->find('main', true);

        // Should have exactly 2 LineDiffs: one for the removal at 10, one for the insertion at 11.
        // If --- and +++ were treated as diffs, we'd have more.
        self::assertCount(2, $differences->lineDiffs);
        self::assertSame(10, $differences->lineDiffs[0]->lineNumber);
        self::assertSame(11, $differences->lineDiffs[1]->lineNumber);
    }

    #[Test]
    public function multipleConsecutiveRemovalsIncrementOldLineNumber(): void
    {
        // Multiple consecutive `-` lines should each get a different line number
        $this->gitRunner->addResponse('--name-status', ["M\tsrc/Foo.php"]);
        $this->gitRunner->addResponse('-- src/Foo.php', [
            'diff --git a/src/Foo.php b/src/Foo.php',
            '--- a/src/Foo.php',
            '+++ b/src/Foo.php',
            '@@ -10,3 +10,0 @@',
            '-line 10',
            '-line 11',
            '-line 12',
        ]);

        $differences = $this->diffFinder->find('main', true);

        $lineNumbers = array_map(static fn ($ld): int => $ld->lineNumber, $differences->lineDiffs);
        self::assertSame([10, 11, 12], $lineNumbers);
    }

    #[Test]
    public function multipleModifiedFilesAccumulateLineDiffs(): void
    {
        // Two files both modified — verifies the spread `[...$lineDiffs, ...new]` accumulates
        // (rather than replacing) line diffs across iterations
        $this->gitRunner->addResponse('--name-status', [
            "M\tsrc/Foo.php",
            "M\tsrc/Bar.php",
        ]);
        $this->gitRunner->addResponse('-- src/Foo.php', [
            'diff --git a/src/Foo.php b/src/Foo.php',
            '--- a/src/Foo.php',
            '+++ b/src/Foo.php',
            '@@ -10,1 +10,0 @@',
            '-line 10',
        ]);
        $this->gitRunner->addResponse('-- src/Bar.php', [
            'diff --git a/src/Bar.php b/src/Bar.php',
            '--- a/src/Bar.php',
            '+++ b/src/Bar.php',
            '@@ -20,1 +20,0 @@',
            '-line 20',
        ]);

        $differences = $this->diffFinder->find('main', true);

        // We should have line diffs from BOTH files, not just the last one
        self::assertCount(2, $differences->lineDiffs);
        $fileNames = array_map(static fn ($ld): string => $ld->fileName, $differences->lineDiffs);
        sort($fileNames);
        self::assertSame(['src/Bar.php', 'src/Foo.php'], $fileNames);
    }

    #[Test]
    public function pureInsertionAtVeryStartUsesLineOne(): void
    {
        // For a hunk @@ -0,0 +1,1 @@ (insertion before any existing line),
        // oldLineNumber is 0. The max(1, 0) ensures we record LineDiff at line 1
        // since LineDiff requires positive line numbers.
        $this->gitRunner->addResponse('--name-status', ["M\tsrc/Foo.php"]);
        $this->gitRunner->addResponse('-- src/Foo.php', [
            'diff --git a/src/Foo.php b/src/Foo.php',
            '--- a/src/Foo.php',
            '+++ b/src/Foo.php',
            '@@ -0,0 +1,1 @@',
            '+new first line',
        ]);

        $differences = $this->diffFinder->find('main', true);

        self::assertCount(1, $differences->lineDiffs);
        self::assertSame(1, $differences->lineDiffs[0]->lineNumber);
    }
}
