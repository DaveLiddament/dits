  function handlePerson(string $name): void {
    $person = lookup($name);
    if ($person->isManager()) {
        $salary = $person->getSalary();
        $salary += 1000;
        $person->setSalary($salary);
    } else {
       $salary = $person->getSalary();
       $salary -= 1000;
       $person->setSalary();
       $person->fire();
    }
    save($person);
  }
