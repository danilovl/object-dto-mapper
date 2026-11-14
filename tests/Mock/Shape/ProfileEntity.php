<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

use Danilovl\ObjectDtoMapper\Tests\Mock\Conversion\AddressEntity;

final class ProfileEntity
{
    public function __construct(public ?AddressEntity $address = null) {}
}
