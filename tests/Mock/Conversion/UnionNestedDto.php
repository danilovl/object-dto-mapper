<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class UnionNestedDto
{
    public function __construct(
        #[Map(source: 'value')]
        public AddressDto|int $address
    ) {}
}
