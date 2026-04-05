# Changes

A `Changes` object holds the set of detected differences and provides a way to query whether a given file and line has changed.

## Construction

- A list of `FileDiff` (whole-file changes)
- A list of `LineDiff` (line-level changes)

## `hasChanged(string $fileName, int $lineNumber): bool`

1. If any `FileDiff` matches the given `$fileName`, return `true`.
2. If any `LineDiff` matches both `$fileName` and `$lineNumber`, return `true`.
3. Otherwise return `false`.
