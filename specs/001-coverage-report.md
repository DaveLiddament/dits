# Coverage report spec

A test coverage report `TestCoverageReport` has:

- An commit identifier (e.g git SHA) `CommitIdentifier`
- One of more `TestCoverage`

Each `TesgeCoverage` has:
- A name `TestName` this completely identifies the test
- One or more  `LineCoverage` entries 

A `LineCoverage` entry has:
- a file name (relative to the project route)
- a line number (must be positive)
