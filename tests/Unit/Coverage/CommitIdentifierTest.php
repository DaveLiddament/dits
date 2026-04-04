<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Coverage;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('001-coverage-report')]
final class CommitIdentifierTest extends TestCase
{
    #[Test]
    public function validConstruction(): void
    {
        $commitIdentifier = new CommitIdentifier('abc123def456');

        self::assertSame('abc123def456', $commitIdentifier->identifier);
    }

    #[Test]
    public function rejectsEmptyIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CommitIdentifier('');
    }
}
