# merge-tcr command

Merges multiple JSON Test Coverage Reports (TCRs) into a single TCR.

## Usage

```
bin/merge-tcr file1.json file2.json [file3.json ...]
```

Shell glob expansion works: `bin/merge-tcr coverage/*.json`

## Requirements

- At least 2 TCR files must be provided.
- All TCR files must have the same `commitIdentifier`. If they differ, the command fails with an error.

## Output

Streams the merged TCR as JSON to stdout. The merged report contains **all** `testCoverages` from every input file, under the shared `commitIdentifier`. No deduplication is performed — duplicate test names from different inputs are preserved.

## Errors

If any input file is missing, unreadable, or contains an unsupported TCR version, the command fails with exit code 1 and prints a clear error message identifying the offending file.
