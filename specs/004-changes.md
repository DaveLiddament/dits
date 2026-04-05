# Changes and Differences

## Differences

A `Differences` value object holds the raw set of detected changes:

- A list of `FileDiff` (whole-file changes)
- A list of `LineDiff` (line-level changes, including insertion points)

## Changes

A `Changes` object wraps a `Differences` and provides a way to query whether a given file and line has changed.

### `hasChanged(string $fileName, int $lineNumber): bool`

1. If any `FileDiff` matches the given `$fileName`, return `true`.
2. If any `LineDiff` matches `$fileName` and the line number is within ±1 of `$lineNumber` (fuzz), return `true`.
3. Otherwise return `false`.

The fuzz ensures that insertions or changes to adjacent lines (e.g. non-executable lines like `} else {`) still trigger test selection for nearby covered lines.
