<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoWithTransformStringCallable
{
    public function __construct(
        public string $name,
        public int $age,
        #[Map(transform: 'trim')]
        public ?string $email = null
    ) {}
}
