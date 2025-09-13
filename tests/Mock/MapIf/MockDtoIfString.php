<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfString
{
    public function __construct(
        #[Map(if: [IfCallable::class, 'useFlagMapName'])]
        public string $name,
        public int $age,
        public ?string $email = null,
    ) {}
}
