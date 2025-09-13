<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Entity;

final class MockEntityWithFlags
{
    public function __construct(
        public string $name,
        public int $age,
        public ?string $email = null,
        public bool $mapName = true,
        public bool $mapAge = true,
        public bool $mapEmail = true,
    ) {}
}
