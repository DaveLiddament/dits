<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Coverage;

use DaveLiddament\TestSelector\Coverage\LineCoverage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('001-coverage-report')]
final class LineCoverageTest extends TestCase
{
    #[Test]
    public function validConstruction(): void
    {
        $lineCoverage = new LineCoverage('src/Foo.php', 42);

        self::assertSame('src/Foo.php', $lineCoverage->fileName);
        self::assertSame(42, $lineCoverage->lineNumber);
    }

    #[Test]
    public function rejectsEmptyFileName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LineCoverage('', 1);
    }

    #[Test]
    public function rejectsZeroLineNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LineCoverage('src/Foo.php', 0);
    }

    #[Test]
    public function rejectsNegativeLineNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LineCoverage('src/Foo.php', -1);
    }
}
