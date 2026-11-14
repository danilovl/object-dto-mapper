<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoTransformFromContainerClassString
{
    public function __construct(
        #[Map(transform: PrefixTransformer::class)]
        public string $name
    ) {}
}
