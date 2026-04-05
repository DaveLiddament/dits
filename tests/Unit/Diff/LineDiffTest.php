<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Diff;

use DaveLiddament\TestSelector\Diff\LineDiff;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('003-diff-value-objects')]
final class LineDiffTest extends TestCase
{
    #[Test]
    public function validConstruction(): void
    {
        $lineDiff = new LineDiff('src/Foo.php', 42);

        self::assertSame('src/Foo.php', $lineDiff->fileName);
        self::assertSame(42, $lineDiff->lineNumber);
    }

    #[Test]
    public function rejectsEmptyFileName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LineDiff('', 1);
    }

    #[Test]
    public function rejectsZeroLineNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LineDiff('src/Foo.php', 0);
    }

    #[Test]
    public function rejectsNegativeLineNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LineDiff('src/Foo.php', -1);
    }
}
