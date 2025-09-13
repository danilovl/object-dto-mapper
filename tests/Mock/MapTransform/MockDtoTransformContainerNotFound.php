<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoTransformContainerNotFound
{
    public function __construct(
        public string $name,
        public int $age,
        #[Map(transformContainer: 'not.existing.transform')]
        public ?string $email = null,
    ) {}
}
