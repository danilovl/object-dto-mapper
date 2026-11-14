<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfFalseWithDefault
{
    public function __construct(
        #[Map(if: false)]
        public string $name = 'hidden'
    ) {}
}
