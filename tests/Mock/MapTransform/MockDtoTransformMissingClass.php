<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoTransformMissingClass
{
    public function __construct(
        #[Map(transform: 'Danilovl\\Missing\\Transformer')]
        public string $name
    ) {}
}
