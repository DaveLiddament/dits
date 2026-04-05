<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Diff;

use DaveLiddament\TestSelector\Diff\Changes;
use DaveLiddament\TestSelector\Diff\FileDiff;
use DaveLiddament\TestSelector\Diff\LineDiff;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('004-changes')]
final class ChangesTest extends TestCase
{
    #[Test]
    public function returnsTrueWhenFileMatchesFileDiff(): void
    {
        $changes = new Changes(
            [new FileDiff('src/Foo.php')],
            [],
        );

        self::assertTrue($changes->hasChanged('src/Foo.php', 10));
    }

    #[Test]
    public function returnsTrueForAnyLineWhenFileMatchesFileDiff(): void
    {
        $changes = new Changes(
            [new FileDiff('src/Foo.php')],
            [],
        );

        self::assertTrue($changes->hasChanged('src/Foo.php', 1));
        self::assertTrue($changes->hasChanged('src/Foo.php', 999));
    }

    #[Test]
    public function returnsTrueWhenFileAndLineMatchLineDiff(): void
    {
        $changes = new Changes(
            [],
            [new LineDiff('src/Foo.php', 42)],
        );

        self::assertTrue($changes->hasChanged('src/Foo.php', 42));
    }

    #[Test]
    public function returnsFalseWhenLineDoesNotMatch(): void
    {
        $changes = new Changes(
            [],
            [new LineDiff('src/Foo.php', 42)],
        );

        self::assertFalse($changes->hasChanged('src/Foo.php', 10));
    }

    #[Test]
    public function returnsFalseWhenFileDoesNotMatch(): void
    {
        $changes = new Changes(
            [],
            [new LineDiff('src/Foo.php', 42)],
        );

        self::assertFalse($changes->hasChanged('src/Bar.php', 42));
    }

    #[Test]
    public function returnsFalseWhenNoChanges(): void
    {
        $changes = new Changes([], []);

        self::assertFalse($changes->hasChanged('src/Foo.php', 10));
    }

    #[Test]
    public function fileDiffTakesPriorityOverLineDiff(): void
    {
        $changes = new Changes(
            [new FileDiff('src/Foo.php')],
            [new LineDiff('src/Foo.php', 42)],
        );

        // Returns true via FileDiff even for a line not in LineDiff
        self::assertTrue($changes->hasChanged('src/Foo.php', 99));
    }

    #[Test]
    public function matchesCorrectFileAmongMultiple(): void
    {
        $changes = new Changes(
            [new FileDiff('src/Foo.php')],
            [new LineDiff('src/Bar.php', 10)],
        );

        self::assertTrue($changes->hasChanged('src/Foo.php', 1));
        self::assertTrue($changes->hasChanged('src/Bar.php', 10));
        self::assertFalse($changes->hasChanged('src/Bar.php', 20));
        self::assertFalse($changes->hasChanged('src/Baz.php', 1));
    }
}
