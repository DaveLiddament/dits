# End-to-end test

A full pipeline test using a real git repository:

1. Create a temp directory and initialise a git repo.
2. Commit source files with known coverage.
3. Generate a TCR from coverage XML fixtures.
4. Make a code change and commit.
5. Run dits with the TCR.
6. Verify the correct tests are selected.
7. Clean up.
