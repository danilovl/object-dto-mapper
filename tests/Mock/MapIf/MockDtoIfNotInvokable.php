<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfNotInvokable
{
    public function __construct(
        #[Map(if: NotInvokable::class)]
        public ?string $name = null
    ) {}
}
