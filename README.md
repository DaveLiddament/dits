# DITS — Diff-Informed Test Selector

A PHP tool that intelligently selects which tests to run based on code changes, using line-level coverage data.

## The Problem

Large test suites, particularly integration and end-to-end tests, can take a long time to run. During local development you typically only change a small portion of the codebase, yet you're forced to either run the entire suite or manually guess which tests are relevant.

## How It Works

DITS uses recorded line-level coverage data to determine exactly which tests exercise the code you've changed, so only those tests are run.

### 1. Collect Coverage (CI)

Every time code is merged into the main branch, run PHPUnit with `--coverage-xml` to collect per-test line coverage. Then use `generate-tcr` to convert this into a JSON Test Coverage Report (TCR):

```bash
# On CI after merge to main
phpunit --coverage-xml=build/coverage-xml
bin/generate-tcr build/coverage-xml > tcr.json
```

The TCR records which tests executed which lines, along with the git commit SHA.

### 2. Select Tests (Local)

When a developer runs `dits` locally, it reads the TCR, diffs the current state against the commit the coverage was collected at, and outputs only the tests that need to run:

```bash
bin/dits < tcr.json
```

### 3. Run Selected Tests

Pipe the output directly into PHPUnit:

```bash
phpunit $(bin/dits --format=phpunit-filter < tcr.json)
```

## Installation

```bash
composer require --dev dave-liddament/test-selector
```

## Commands

### generate-tcr

Generates a JSON Test Coverage Report from PHPUnit's `--coverage-xml` output.

```bash
bin/generate-tcr <coverage-xml-dir> [options]
```

**Arguments:**
- `coverage-xml-dir` — path to the PHPUnit `--coverage-xml` output directory

**Options:**
- `--source-dir`, `-s` (default: `src/`) — source directory relative to project root
- `--commit`, `-c` — commit SHA to record; defaults to `git rev-parse HEAD`
- `--config` — path to config file

**Example:**
```bash
bin/generate-tcr build/coverage-xml --source-dir=src/ > tcr.json
```

### merge-tcr

Merges multiple TCR files into one. Useful when coverage is collected across multiple test suites or CI jobs.

```bash
bin/merge-tcr <file1.json> <file2.json> [file3.json ...]
```

All input TCRs must have the same commit identifier. Shell glob expansion works:

```bash
bin/merge-tcr coverage/*.json > merged-tcr.json
```

### dits

The main command. Reads a TCR from stdin, finds code changes since the coverage was collected, and outputs the tests that need to run.

```bash
bin/dits [options] < tcr.json
```

**Options:**
- `--include-unstaged`, `-u` — include uncommitted working tree changes and untracked files
- `--format`, `-f` (default: `list`) — output format: `list`, `phpunit-filter`, `json`
- `--config` — path to config file

**Output formats:**

`list` (default) — one test name per line:
```
App\Tests\FooTest::testBar
App\Tests\BazTest::testQux
```

`phpunit-filter` — ready for PHPUnit's `--filter` flag:
```
--filter='App\\Tests\\FooTest::testBar|App\\Tests\\BazTest::testQux'
```

`json` — JSON array:
```json
["App\\Tests\\FooTest::testBar", "App\\Tests\\BazTest::testQux"]
```

## Configuration

Create a `.dits.php` file in your project root:

```php
<?php

use DaveLiddament\TestSelector\Config\DitsConfig;

return DitsConfig::create()
    ->sourceDir('src/')
    ->includeUnstaged()
    ->format('phpunit-filter');
```

**Available options:**

| Method | Default | Used by |
|---|---|---|
| `sourceDir(string)` | `src/` | generate-tcr |
| `commit(string)` | `git rev-parse HEAD` | generate-tcr |
| `includeUnstaged()` | `false` | dits |
| `format(string)` | `list` | dits |

CLI options always take precedence over config file values.

To use a config file from a different location: `--config=path/to/config.php`

## Typical CI Workflow

### On merge to main

```bash
# Run tests with coverage
XDEBUG_MODE=coverage phpunit --coverage-xml=build/coverage-xml

# Generate TCR
bin/generate-tcr build/coverage-xml > tcr.json

# Store tcr.json as a CI artifact, commit it, or upload to shared storage
```

### On feature branches / local development

```bash
# Fetch the latest TCR from wherever it's stored
# Then run only the affected tests:
phpunit $(bin/dits --format=phpunit-filter -u < tcr.json)
```

If no tests are selected (nothing changed that matches coverage), the output is empty and PHPUnit runs with an empty filter (running nothing).

## How Test Selection Works

1. **Coverage data** maps each test to the specific source lines it executes.
2. **Git diff** identifies which files and lines changed since the coverage commit.
3. **Fuzz matching** checks changed lines ±1 to catch insertions and changes to non-executable lines (like `} else {`).
4. **File-level changes** (new, deleted, renamed files) select all tests covering that file.
5. **Untracked files** (with `-u`) are treated as file-level changes.

The approach is intentionally conservative — it may select a few extra tests, but it won't miss tests that should run.

## Requirements

- PHP 8.5+
- PHPUnit (for coverage collection)
- Xdebug or PCOV (for line-level coverage)
- Git

## Licence

MIT
