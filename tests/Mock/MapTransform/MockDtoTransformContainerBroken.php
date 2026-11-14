<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoTransformContainerBroken
{
    public function __construct(
        #[Map(transformContainer: 'broken.service')]
        public string $name
    ) {}
}
