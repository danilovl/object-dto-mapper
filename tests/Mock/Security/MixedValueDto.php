<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Security;

final class MixedValueDto
{
    public function __construct(public mixed $value) {}
}
