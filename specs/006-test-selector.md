# Test Selector

Given a `TestCoverageReport` and a `Differences` object, determine which tests need to run.

## Logic

Build inverse indexes from the coverage report (one pass through the coverage data):
- `fileToTests` — file name → set of tests covering any line in that file
- `fileLineToTests` — file name + line number → set of tests covering that exact line

Then iterate the diffs:
1. For each `FileDiff`, union the result with all tests covering that file.
2. For each `LineDiff`, union the result with all tests covering line, line-1, and line+1 (the ±1 fuzz catches insertions and changes to adjacent non-executable lines).

Returns the list of `TestName` for tests that need to run, deduplicated.

This is the inverse of the naive approach — we iterate the (small) diff and look up affected tests, rather than iterating every coverage entry to check if it's affected. This scales with diff size rather than coverage size.



## Examples

Assume each diff starts are line 1. 


### Original code

| Test   | Lines executed | 
|--------|----------------|
| Test 1 | 2, 3, 4, 5, 6, 10 |
| Test 2 | 2, 3, 8, 10    |

```php
  function handlePerson(string $name): void {
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
       $person->fire();
    }
    save($person);
  }
```

### Example 1: Single line updated

Select Tests 1 and 2.

```diff
  function handlePerson(string $name): void {
    $person = lookup($name);
-   if ($person->isManager()) {
+   if ($person->isSupervisor()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
       $person->fire();
    }
    save($person);
  }
```

### Example 2: Single line removed

Select Tests 1.

```diff
  function handlePerson(string $name): void {
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
-       $person->setSalary($salary);
    } else {
       $person->fire();
    }
    save($person);
  }
```

### Example 3: Single line added

Tests selected: 1, 2

```diff
  function handlePerson(string $name): void {
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
+      $person->blankEmail();
       $person->fire();
    }
    save($person);
  }
```

### Example 4: Multiple lines added

Tests selected: 1, 2

```diff
  function handlePerson(string $name): void {
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
+      $salary = $person->getSalary();
+      $salary -= 1000;
+      $person->setSalary();
       $person->fire();
    }
    save($person);
  }
```

### Example 5: Line added at start

Tests selected: 1, 2

```diff
  function handlePerson(string $name): void {
+   $name = strtolower($name);
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
       $person->fire();
    }
    save($person);
  }
```

### Example 6: Line added at end

Tests selected: 1, 2

```diff
  function handlePerson(string $name): void {
+   $name = strtolower($name);
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
       $person->fire();
    }
    save($person);
+   email($person);
  }
```

### Example 7: Statement removed

Tests selected: 1, 2

```diff
  function handlePerson(string $name): void {
+   $name = strtolower($name);
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
-   } else {
       $person->fire();
    }
    save($person);
  }
```
