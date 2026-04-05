  function handlePerson(string $name): void {
    $name = strtolower($name);
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
       $person->fire();
    }
    save($person);
    email($person);
  }
