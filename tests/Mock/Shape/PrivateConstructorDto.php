<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class PrivateConstructorDto
{
    private function __construct(public string $name) {}

    public static function create(string $name): self
    {
        return new self($name);
    }
}
