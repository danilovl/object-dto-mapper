<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class GetterDto
{
    public function __construct(
        public string $name,
        public bool $active,
        public bool $children
    ) {}
}
