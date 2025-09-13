<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfCallable
{
    public function __construct(
        public string $name,
        public int $age,
        #[Map(if: [IfCallable::class, 'allowIfValueNotEmpty'])]
        public ?string $email = null,
    ) {}
}
