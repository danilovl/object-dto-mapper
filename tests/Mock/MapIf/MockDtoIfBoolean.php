<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfBoolean
{
    public function __construct(
        #[Map(if: true)]
        public string $name,
        #[Map(if: false)]
        public ?int $age = null,
        public ?string $email = null,
    ) {}
}
