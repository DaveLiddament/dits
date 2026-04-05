  function handlePerson(string $name): void {
    $name = strtolower($name);
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
       $person->fire();
    }
    save($person);
  }
