<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

final class ImplementingAddressDto implements AddressInterface
{
    public function __construct(public string $city) {}
}
