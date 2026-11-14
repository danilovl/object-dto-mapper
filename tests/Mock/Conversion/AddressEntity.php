<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

final class AddressEntity
{
    public function __construct(
        public string $city,
        public string $street = 'Main street'
    ) {}
}
