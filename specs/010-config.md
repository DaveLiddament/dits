# Configuration

## Config file

By default, `.dits.php` in the project root. Can be overridden with `--config=path`.

- If `--config` is provided and the file doesn't exist → error.
- If no `--config` and `.dits.php` doesn't exist → default config (all defaults).
- CLI options always take precedence over config values.

## Format

```php
<?php

use DaveLiddament\TestSelector\Config\DitsConfig;

return DitsConfig::create()
    ->sourceDir('src/')
    ->commit('abc123')
    ->includeUnstaged()
    ->format('phpunit-filter');
```

All methods are optional — omitted values use defaults.

## Available options

| Method | Default | Used by |
|---|---|---|
| `sourceDir(string)` | `src/` | generate-tcr |
| `commit(string)` | `git rev-parse HEAD` | generate-tcr |
| `includeUnstaged()` | `false` | dits |
| `format(string)` | `list` | dits |

## CLI override

CLI options always win. For example, `--source-dir=lib/` overrides whatever `sourceDir()` is set in the config.
