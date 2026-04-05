<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Config;

use DaveLiddament\TestSelector\Config\DitsConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('010-config')]
final class DitsConfigTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $config = DitsConfig::create();

        self::assertSame('src/', $config->getSourceDir());
        self::assertNull($config->getCommit());
        self::assertFalse($config->isIncludeUnstaged());
        self::assertSame('list', $config->getFormat());
    }

    #[Test]
    public function fluentInterface(): void
    {
        $config = DitsConfig::create()
            ->sourceDir('lib/')
            ->commit('abc123')
            ->includeUnstaged()
            ->format('json');

        self::assertSame('lib/', $config->getSourceDir());
        self::assertSame('abc123', $config->getCommit());
        self::assertTrue($config->isIncludeUnstaged());
        self::assertSame('json', $config->getFormat());
    }
}
