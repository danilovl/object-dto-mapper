<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoTransformSourceAware
{
    public function __construct(
        #[Map(transform: SourceAwareTransformer::class)]
        public string $name
    ) {}
}
