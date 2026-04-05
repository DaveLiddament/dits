<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\Config;

final class DitsConfig
{
    private string $sourceDir = 'src/';
    private ?string $commit = null;
    private bool $includeUnstaged = false;
    private string $format = 'list';

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function sourceDir(string $sourceDir): self
    {
        $this->sourceDir = $sourceDir;

        return $this;
    }

    public function commit(string $commit): self
    {
        $this->commit = $commit;

        return $this;
    }

    public function includeUnstaged(): self
    {
        $this->includeUnstaged = true;

        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getSourceDir(): string
    {
        return $this->sourceDir;
    }

    public function getCommit(): ?string
    {
        return $this->commit;
    }

    public function isIncludeUnstaged(): bool
    {
        return $this->includeUnstaged;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
