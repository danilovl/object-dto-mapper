<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Entity;

class MockEntityWithNonReadableProperty
{
    public string $name;

    public int $age;

    private ?string $email;

    public function __construct(string $name, int $age, ?string $email = null)
    {
        $this->name = $name;
        $this->age = $age;
        $this->email = $email;
        $unused = $this->email;
    }
}
