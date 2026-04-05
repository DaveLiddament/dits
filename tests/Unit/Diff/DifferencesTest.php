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
}
