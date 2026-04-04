# Test Selector

A PHP tool that intelligently selects which tests to run based on code changes, using line-level coverage data.

## The Problem

Large test suites, particularly integration and end-to-end tests, can take a long time to run. 
During local development you typically only change a small portion of the codebase, yet you're forced to either run the entire suite or manually guess which tests are relevant.

## How It Works

Test Selector uses recorded line-level coverage data to determine exactly which tests exercise the code you've changed, so only those tests are run.

### 1. Collect Coverage (CI)

Every time code is merged into the main branch, PHPUnit runs with line coverage collection enabled. This produces a mapping of every test to the specific lines of code it executes:

```
TestA -> src/Foo.php:10, src/Foo.php:11, src/Bar.php:25
TestB -> src/Foo.php:42, src/Baz.php:7
TestC -> src/Bar.php:30, src/Bar.php:31
```

This coverage data is stored against the git reference (commit SHA) of the main branch.

### 2. Detect Changes (Local)

When a developer runs Test Selector locally, it compares the current working tree against the reference point (typically the main branch). It identifies:

- **Modified lines** — lines that exist in the reference but have been changed.
- **Added lines** — new lines inserted between lines that appear in the coverage data.

### 3. Select Tests

Using the coverage map and the set of changed/added lines, Test Selector determines which tests cover the affected code and runs only those tests.

For example, if you modify `src/Foo.php` line 42, only `TestB` needs to run — not the entire suite.

## Installation

```bash
composer require --dev dave-liddament/test-selector
```

## Quick Start

_Coming soon — the project is under active development._

## Requirements

- PHP 8.5+
- PHPUnit

## Licence

MIT
