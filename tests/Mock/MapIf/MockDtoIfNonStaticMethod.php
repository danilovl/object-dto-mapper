<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfNonStaticMethod
{
    public function __construct(
        #[Map(if: [NotInvokable::class, 'check'])]
        public ?string $name = null
    ) {}
}
