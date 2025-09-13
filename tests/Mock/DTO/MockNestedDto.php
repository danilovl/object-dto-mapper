<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\DTO;

readonly class MockNestedDto
{
    public function __construct(
        public string $name,
        public MockDto $entity
    ) {}
}
