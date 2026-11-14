<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class NullableNameDto
{
    public function __construct(public ?string $name) {}
}
