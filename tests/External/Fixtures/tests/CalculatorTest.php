<?php

declare(strict_types=1);

namespace Fixtures\Tests;

use Fixtures\Calculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('002-phpunit-coverage-parser')]
final class CalculatorTest extends TestCase
{
    #[Test]
    public function add(): void
    {
        $calculator = new Calculator();

        self::assertSame(5, $calculator->add(2, 3));
    }
}
