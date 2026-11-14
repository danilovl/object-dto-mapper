<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfStaticMethodString
{
    public function __construct(
        public string $name,
        #[Map(if: IfCallable::class . '::allowIfValueNotEmpty')]
        public ?string $email = null
    ) {}
}
