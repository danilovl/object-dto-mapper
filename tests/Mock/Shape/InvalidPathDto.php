<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class InvalidPathDto
{
    public function __construct(
        #[Map(source: 'name[')]
        public ?string $name
    ) {}
}
