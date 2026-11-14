<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoTransformToNull
{
    public function __construct(
        #[Map(transform: NullTransformer::class)]
        public string $name
    ) {}
}
