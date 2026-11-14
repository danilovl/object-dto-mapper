<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class GetterEntity
{
    public function __construct(
        private string $name,
        private bool $active,
        private bool $children
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function hasChildren(): bool
    {
        return $this->children;
    }
}
