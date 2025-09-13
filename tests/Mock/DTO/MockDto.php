<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\DTO;

readonly class MockDto
{
    public function __construct(
        public string $name,
        public int $age,
        public ?string $email = null
    ) {}
}
