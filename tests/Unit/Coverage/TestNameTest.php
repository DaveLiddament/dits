<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Coverage;

use DaveLiddament\TestSelector\Coverage\TestName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('001-coverage-report')]
final class TestNameTest extends TestCase
{
    #[Test]
    public function validConstruction(): void
    {
        $testName = new TestName('App\\Tests\\FooTest::testBar');

        self::assertSame('App\\Tests\\FooTest::testBar', $testName->testName);
    }

    #[Test]
    public function rejectsEmptyTestName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TestName('');
    }
}
