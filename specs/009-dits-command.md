# dits command

Selects tests to run based on code changes since the coverage was collected.

## Usage

```
cat coverage.json | bin/dits [--include-unstaged] [--format=list]
bin/dits --format=phpunit-filter < coverage.json
```

## Input

TCR JSON via stdin. The `commitIdentifier` from the TCR is used as the git ref to diff against.

## Options

- `--include-unstaged` / `-u` — include uncommitted working tree changes and untracked files
- `--format` / `-f` (default: `list`) — output format: `list`, `phpunit-filter`, `json`

## Output formats

### `list` (default)
One test name per line.

### `phpunit-filter`
`--filter='Test1|Test2'` with backslashes escaped, ready for PHPUnit.

### `json`
JSON array of test name strings.

## Exit codes

- **0** — success (tests selected, or no tests to run)
- **1** — input error (invalid TCR JSON, empty stdin, unsupported TCR version). The underlying error message is included in the output.
- **2** — git error (diff failed, or commit not found in local repo)
