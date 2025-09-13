<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfInvalidClassString
{
    public function __construct(
        #[Map(if: '\\Invalid\\MissingCallable')]
        public string $name,
        public int $age,
        public ?string $email = null,
    ) {}
}
