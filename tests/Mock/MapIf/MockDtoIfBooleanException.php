<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfBooleanException
{
    public function __construct(
        public string $name,
        #[Map(if: false)]
        public int $age,
        public ?string $email = null,
    ) {}
}
