<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class PropertyPathDto
{
    public function __construct(
        #[Map(source: 'profile.address.city')]
        public ?string $city,
        #[Map(source: 'profile.address.street')]
        public string $street = 'unknown',
        #[Map(source: 'settings[theme]')]
        public ?string $theme = null
    ) {}
}
