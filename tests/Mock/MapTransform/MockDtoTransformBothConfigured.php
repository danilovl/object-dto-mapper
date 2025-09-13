<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoTransformBothConfigured
{
    public function __construct(
        public string $name,
        public int $age,
        #[Map(transform: 'trim', transformContainer: 'service.id')]
        public ?string $email = null,
    ) {}
}
