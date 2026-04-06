# Differences

A `Differences` value object holds the raw set of detected changes from a diff:

- A list of `FileDiff` (whole-file changes — added, removed, renamed)
- A list of `LineDiff` (line-level changes, including insertion points)

Both lists are reindexed to sequential `0..N-1` keys in the constructor.

This is a pure data container. It is consumed by `TestSelector` which builds inverse indexes from the `TestCoverageReport` and uses the diffs to look up which tests are affected (with ±1 fuzz on line numbers to catch insertions and changes to adjacent lines).
