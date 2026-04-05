<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Tests\Unit\Config;

use DaveLiddament\TestSelector\Config\ConfigLoader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[Ticket('010-config')]
final class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new ConfigLoader();
    }

    #[Test]
    public function loadsExplicitConfigFile(): void
    {
        $config = $this->loader->load(__DIR__.'/Fixtures/valid-config.php');

        self::assertSame('lib/', $config->getSourceDir());
        self::assertSame('custom-sha', $config->getCommit());
        self::assertTrue($config->isIncludeUnstaged());
        self::assertSame('json', $config->getFormat());
    }

    #[Test]
    public function loadsMinimalConfigFile(): void
    {
        $config = $this->loader->load(__DIR__.'/Fixtures/minimal-config.php');

        self::assertSame('src/', $config->getSourceDir());
        self::assertNull($config->getCommit());
        self::assertFalse($config->isIncludeUnstaged());
        self::assertSame('list', $config->getFormat());
    }

    #[Test]
    public function throwsWhenExplicitConfigFileMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config file not found');

        $this->loader->load('/nonexistent/config.php');
    }

    #[Test]
    public function throwsWhenConfigReturnsWrongType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must return an instance of');

        $this->loader->load(__DIR__.'/Fixtures/invalid-config.php');
    }

    #[Test]
    public function returnsDefaultConfigWhenNoFileExists(): void
    {
        $loader = new ConfigLoader('/tmp/no-such-dir');
        $config = $loader->load(null);

        self::assertSame('src/', $config->getSourceDir());
        self::assertNull($config->getCommit());
    }

    #[Test]
    public function loadsDefaultDitsPhpWhenPresent(): void
    {
        // Point the project root at the Fixtures directory which has valid-config.php
        // We need a .dits.php there — create a symlink or just use a temp dir
        $tempDir = sys_get_temp_dir().'/dits-config-test-'.bin2hex(random_bytes(4));
        mkdir($tempDir);
        copy(__DIR__.'/Fixtures/valid-config.php', $tempDir.'/.dits.php');

        try {
            $loader = new ConfigLoader($tempDir);
            $config = $loader->load(null);

            self::assertSame('lib/', $config->getSourceDir());
            self::assertTrue($config->isIncludeUnstaged());
        } finally {
            unlink($tempDir.'/.dits.php');
            rmdir($tempDir);
        }
    }
}
