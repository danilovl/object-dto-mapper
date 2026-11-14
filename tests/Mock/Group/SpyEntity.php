<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Group;

final class SpyEntity
{
    /**
     * @var array<string, int>
     */
    public array $calls = [];

    public function getName(): string
    {
        return $this->track('name', 'John Doe');
    }

    public function getAge(): int
    {
        return $this->track('age', 30);
    }

    public function getEmail(): string
    {
        return $this->track('email', 'john@example.com');
    }

    /**
     * @template T
     * @param T $value
     * @return T
     */
    private function track(string $name, mixed $value): mixed
    {
        $this->calls[$name] = ($this->calls[$name] ?? 0) + 1;

        return $value;
    }
}
