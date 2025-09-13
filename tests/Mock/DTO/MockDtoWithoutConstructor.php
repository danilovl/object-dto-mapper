<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\DTO;

class MockDtoWithoutConstructor
{
    public string $name;

    public int $age;

    public ?string $email = null;
}
