<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Entity;

readonly class MockEntity
{
    public function __construct(
        public string $name,
        public int $age,
        public ?string $email = null
    ) {}
}
