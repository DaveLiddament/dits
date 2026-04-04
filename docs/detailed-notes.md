# Detailed Notes

Technical notes and design considerations for Test Selector.

## Coverage Collection

PHPUnit supports line-level coverage via Xdebug (or PCOV). The key output we need is a map from each test to the set of `file:line` pairs it executes. This is collected on CI after every merge to the main branch and stored alongside the commit SHA so that local comparisons have a stable reference point.

### Storage Format

The coverage data has the following information:

- Git commit SHA
- Test name
- Lines executed by each test

The exact storage backend is TBD. Options include a flat file committed to the repo, an artefact attached to the CI run, or an external service.

## Change Detection

Given a reference commit SHA and the current working tree, we need to produce a set of changed lines per file. `git diff` provides this directly — unified diffs contain the exact line ranges that were added, removed, or modified.

### Handling Added Lines

Added lines are not present in the coverage data (they didn't exist when coverage was collected). However, if new lines are inserted between two lines that are covered by a test, it is reasonable to assume the new code is related and that test should run. The heuristic is:

- If a new line is inserted between line N and line M, and any test covers line N or line M, include that test.

We need to think what happens if a line is added before N or after M, as that might affect the outcome of the test. 

### Handling Deleted Lines

If a line that was covered by a test is deleted, the test should be selected — the deletion may break the behaviour the test verifies.

### Handling Moved/Renamed Files

If a file is renamed (detected via `git diff --find-renames`), the coverage data from the old path should map to the new path.

## Test Selection Algorithm

1. Compute the diff between the reference commit and the working tree.
2. For each changed file, determine the set of affected lines.
3. Look up the coverage data for those lines.
4. Collect the union of all tests that cover any affected line.
5. Pass the selected test list to PHPUnit (e.g. via `--filter` or a test suite XML).

## Edge Cases

- **No coverage data available** — fall back to running all tests.
- **New files with no coverage** — no tests will be selected for them (they have no coverage history). The developer should run the full suite or write new tests.
- **Configuration/non-PHP changes** — changes to `phpunit.xml`, `composer.json`, etc. are outside line-level coverage. A conservative approach is to run all tests when these files change.
- **Test file changes** — if a test file itself is modified, that test should always be selected regardless of coverage data.

## Integration Points

- **CI pipeline** — needs a step after merge that runs PHPUnit with coverage and stores the result.
- **Local CLI** — the developer-facing command that performs change detection, test selection, and runs PHPUnit with the selected tests.
- **PHPUnit** — tests are executed via PHPUnit; Test Selector is an orchestrator, not a test runner.
