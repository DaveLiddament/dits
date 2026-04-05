<?php

declare(strict_types=1);

namespace Fixtures\Tests;

use Fixtures\Greeter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('002-phpunit-coverage-parser')]
final class GreeterTest extends TestCase
{
    #[Test]
    public function greet(): void
    {
        $greeter = new Greeter();

        self::assertSame('Hello, World!', $greeter->greet('World'));
    }
}
