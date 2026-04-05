<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Diff;

use DaveLiddament\TestSelector\Diff\FileDiff;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('003-diff-value-objects')]
final class FileDiffTest extends TestCase
{
    #[Test]
    public function validConstruction(): void
    {
        $fileDiff = new FileDiff('src/Service/Logger.php');

        self::assertSame('src/Service/Logger.php', $fileDiff->fileName);
    }

    #[Test]
    public function rejectsEmptyFileName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FileDiff('');
    }
}
