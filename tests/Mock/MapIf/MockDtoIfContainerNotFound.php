<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfContainerNotFound
{
    public function __construct(
        #[Map(ifContainer: 'not.existing.service')]
        public ?string $name,
        public int $age,
        public ?string $email = null,
    ) {}
}
