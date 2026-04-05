# generate-tcr command

Generates a JSON Test Coverage Report (TCR) from PHPUnit's `--coverage-xml` output.

## Usage

```
bin/generate-tcr <coverage-xml-dir> [--source-dir=src/] [--commit=<sha>]
```

## Arguments

- `coverage-xml-dir` (required) — path to the directory containing PHPUnit's `--coverage-xml` output

## Options

- `--source-dir` / `-s` (default: `src/`) — source directory relative to the project root
- `--commit` / `-c` (optional) — commit SHA to record in the TCR; if omitted, runs `git rev-parse HEAD`

## Output

Streams JSON to stdout. The JSON structure is:

```json
{
  "commitIdentifier": "<sha>",
  "testCoverages": [
    {
      "testName": "App\\Tests\\FooTest::testBar",
      "lineCoverages": [
        {"fileName": "src/Foo.php", "lineNumber": 10}
      ]
    }
  ]
}
```
