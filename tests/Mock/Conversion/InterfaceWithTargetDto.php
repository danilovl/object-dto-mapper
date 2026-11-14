<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class InterfaceWithTargetDto
{
    public function __construct(
        #[Map(target: ImplementingAddressDto::class, source: 'value')]
        public AddressInterface $address
    ) {}
}
