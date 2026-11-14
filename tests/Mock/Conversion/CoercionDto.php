<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

final class CoercionDto
{
    public function __construct(
        public string $name,
        public string $age
    ) {}
}
