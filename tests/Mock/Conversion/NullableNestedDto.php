<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class NullableNestedDto
{
    public function __construct(
        #[Map(source: 'value')]
        public ?AddressDto $address
    ) {}
}
