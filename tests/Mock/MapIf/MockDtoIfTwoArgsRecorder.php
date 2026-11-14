<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfTwoArgsRecorder
{
    public function __construct(
        #[Map(if: [ArgumentRecorder::class, 'recordTwo'])]
        public string $name
    ) {}
}
