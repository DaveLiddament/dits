<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Config;

use Webmozart\Assert\Assert;

final class ConfigLoader
{
    private const string DEFAULT_CONFIG_FILE = '.dits.php';

    private string $projectRoot;

    public function __construct(
        ?string $projectRoot = null,
    ) {
        /** @infection-ignore-all Equivalent mutant: getcwd() returns string in normal operation; cast handles edge case where it returns false */
        $this->projectRoot = $projectRoot ?? (string) getcwd();
    }

    /**
     * Loads config from the given path, or the default .dits.php in the project root.
     *
     * - If $configPath is provided and the file doesn't exist, throws an error.
     * - If $configPath is null, looks for .dits.php in the project root. If missing, returns default config.
     */
    public function load(?string $configPath = null): DitsConfig
    {
        if (null !== $configPath) {
            Assert::fileExists($configPath, sprintf('Config file not found: %s', $configPath));

            return $this->loadFile($configPath);
        }

        $defaultPath = $this->projectRoot.'/'.self::DEFAULT_CONFIG_FILE;

        if (!file_exists($defaultPath)) {
            return DitsConfig::create();
        }

        return $this->loadFile($defaultPath);
    }

    private function loadFile(string $path): DitsConfig
    {
        $config = require $path;

        Assert::isInstanceOf($config, DitsConfig::class, sprintf(
            'Config file must return an instance of %s, got %s',
            DitsConfig::class,
            get_debug_type($config),
        ));

        return $config;
    }
}
