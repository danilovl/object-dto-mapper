<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class InterfaceTypedDto
{
    public function __construct(
        #[Map(source: 'value')]
        public AddressInterface $address
    ) {}
}
