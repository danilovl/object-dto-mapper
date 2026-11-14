<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfVariadicRecorder
{
    public function __construct(
        #[Map(if: [ArgumentRecorder::class, 'record'])]
        public string $name
    ) {}
}
