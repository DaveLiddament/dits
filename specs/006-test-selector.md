# Test Selector

Given a `TestCoverageReport` and a `Changes` object, determine which tests need to run.

## Logic

For each test in the coverage report:
1. Iterate through its `LineCoverage` entries.
2. For each entry, ask `Changes::hasChanged(fileName, lineNumber)`.
3. If any returns `true`, select that test (short-circuit to the next test).

Returns the list of `TestName` for tests that need to run.



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
