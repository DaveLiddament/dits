# PHPUnit coverage parser

Parse PHPUnit `--coverage-xml` output into a `TestCoverageReport`.

PHPUnit can produce per-test line-level coverage data. We need a parser that reads this output and produces a `TestCoverageReport` containing which tests covered which lines of which files.

## Format

We use `--coverage-xml` which produces a directory containing:
- `index.xml` — lists all tests and per-file XML references
- Per-file XMLs — contain `<coverage><line nr="X"><covered by="TestName"/></line></coverage>`

File paths in `LineCoverage` are relative to the project root (git root). The parser takes a `sourcePrefix` parameter (e.g. `src/`) and prepends it to the path constructed from the `path` and `name` attributes on the `<file>` element (e.g. `src/` + `Service/` + `Logger.php` = `src/Service/Logger.php`).

If a per-file XML referenced in `index.xml` is missing, it is silently skipped (the parser continues processing subsequent file XMLs).

## Error handling

If `index.xml` or any per-file XML is malformed, the parser throws `InvalidArgumentException` with a clear message identifying which file failed to parse.

## Approach

1. Run PHPUnit with `--coverage-xml` against a project.
2. Parse `index.xml` to discover per-file XMLs.
3. Parse each file XML to extract per-test line coverage.
4. Build a `TestCoverageReport` grouping line coverages by test name.
