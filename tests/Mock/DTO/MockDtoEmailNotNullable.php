<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\DTO;

final class MockDtoEmailNotNullable
{
    public function __construct(
        public string $name,
        public int $age,
        public string $email
    ) {}
}
