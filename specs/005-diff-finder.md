# Diff Finder

Finds differences between the current state and a reference branch, returning a `Differences` object.

## Interface

```
DiffFinder::find(string $branch, bool $includeUnstaged): Differences
```

- `$branch` — the branch to compare against (e.g. `main`)
- `$includeUnstaged` — if `true`, include uncommitted working tree changes; if `false`, only committed changes since the branch

## File classification

Uses `git diff --name-status --find-renames` to classify each changed file:

| Git status | Result |
|---|---|
| `A` (added) | `FileDiff` — new file, no original lines to match against coverage |
| `D` (deleted) | `FileDiff` — entire file removed |
| `R` (renamed) | `FileDiff` — file path changed, coverage data uses old path |
| `M` (modified) | Parse unified diff for changed line numbers → `LineDiff` entries |

## Line-level diff parsing (modified files)

For modified files, parse the unified diff output to find which lines in the **original** file were changed or removed:

- Hunk headers (`@@ -old,count +new,count @@`) give the starting line number in the old file
- Lines starting with `-` are removed/changed lines → produce a `LineDiff`
- Lines starting with `+` are additions → produce a `LineDiff` at the current old line position (deduplicated for consecutive insertions). This captures where in the original file the insertion occurred.
- Uses `--unified=0` so no context lines appear

## Git commands

- `includeUnstaged = true`: `git diff --name-status --find-renames <branch>` and `git diff <branch> -- <file>`, plus `git ls-files --others --exclude-standard` to pick up untracked files as `FileDiff` entries
- `includeUnstaged = false`: `git diff --name-status --find-renames <branch>..HEAD` and `git diff <branch>..HEAD -- <file>` (no untracked files)

## Testability

Git command execution is behind a `GitCommandRunner` interface so DiffFinder can be unit tested with known git output.
