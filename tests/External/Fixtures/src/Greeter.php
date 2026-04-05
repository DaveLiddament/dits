<?php

declare(strict_types=1);

namespace Fixtures;

class Greeter
{
    public function greet(string $name): string
    {
        return 'Hello, '.$name.'!';
    }

    public function farewell(string $name): string
    {
        return 'Goodbye, '.$name.'!';
    }
}
