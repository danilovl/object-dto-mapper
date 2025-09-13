<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Entity;

readonly class MockNestedEntity
{
    public function __construct(
        public string $name,
        public MockEntity $entity
    ) {}
}
