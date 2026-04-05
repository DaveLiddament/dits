# Diff value objects

Value objects representing changes between a reference commit and the current working tree.

## FileDiff

Represents a whole-file change (added, removed, or modified). Any test covering any line in this file should run.

- `fileName` — relative path from project root (e.g. `src/Service/Logger.php`)

## LineDiff

Represents a changed line in the original file (before the diff). Used for targeted test selection.

- `fileName` — relative path from project root
- `lineNumber` — line number in the original file (before changes)
