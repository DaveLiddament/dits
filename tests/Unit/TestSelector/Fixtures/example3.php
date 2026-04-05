  function handlePerson(string $name): void {
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
       $person->blankEmail();
       $person->fire();
    }
    save($person);
  }
