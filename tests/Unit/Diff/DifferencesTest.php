<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Diff;

use DaveLiddament\TestSelector\Diff\Differences;
use DaveLiddament\TestSelector\Diff\FileDiff;
use DaveLiddament\TestSelector\Diff\LineDiff;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('004-changes')]
final class DifferencesTest extends TestCase
{
    #[Test]
    public function validConstruction(): void
    {
        $fileDiff = new FileDiff('src/Foo.php');
        $lineDiff = new LineDiff('src/Bar.php', 10);

        $differences = new Differences([$fileDiff], [$lineDiff]);

        self::assertCount(1, $differences->fileDiffs);
        self::assertSame($fileDiff, $differences->fileDiffs[0]);
        self::assertCount(1, $differences->lineDiffs);
        self::assertSame($lineDiff, $differences->lineDiffs[0]);
    }

    #[Test]
    public function emptyDifferences(): void
    {
        $differences = new Differences([], []);

        self::assertSame([], $differences->fileDiffs);
        self::assertSame([], $differences->lineDiffs);
    }

    #[Test]
    public function preservesMultipleDiffsAsLists(): void
    {
        $fileDiffs = [
            new FileDiff('src/Foo.php'),
            new FileDiff('src/Bar.php'),
        ];
        $lineDiffs = [
            new LineDiff('src/Baz.php', 1),
            new LineDiff('src/Qux.php', 2),
            new LineDiff('src/Qux.php', 3),
        ];

        $differences = new Differences($fileDiffs, $lineDiffs);

        self::assertCount(2, $differences->fileDiffs);
        self::assertCount(3, $differences->lineDiffs);
    }

    #[Test]
    public function reindexesNonSequentialKeys(): void
    {
        // Pass arrays with gap keys; constructor should re-index to a list (0, 1, 2...)
        $fileDiffs = [
            5 => new FileDiff('src/Foo.php'),
            10 => new FileDiff('src/Bar.php'),
        ];
        $lineDiffs = [
            'a' => new LineDiff('src/Baz.php', 1),
            'b' => new LineDiff('src/Qux.php', 2),
        ];

        $differences = new Differences($fileDiffs, $lineDiffs);

        self::assertSame([0, 1], array_keys($differences->fileDiffs));
        self::assertSame([0, 1], array_keys($differences->lineDiffs));
    }
}
