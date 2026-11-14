<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Recursion;

final class CategoryDto
{
    public function __construct(
        public string $name,
        public ?self $parent = null
    ) {}
}
