  function handlePerson(string $name): void {
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
    } else {
       $person->fire();
    }
    save($person);
  }
