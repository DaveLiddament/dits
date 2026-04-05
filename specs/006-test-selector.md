# Test Selector

Given a `TestCoverageReport` and a `Changes` object, determine which tests need to run.

## Logic

For each test in the coverage report:
1. Iterate through its `LineCoverage` entries.
2. For each entry, ask `Changes::hasChanged(fileName, lineNumber)`.
3. If any returns `true`, select that test (short-circuit to the next test).

Returns the list of `TestName` for tests that need to run.
