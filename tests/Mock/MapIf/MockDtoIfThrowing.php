<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfThrowing
{
    public function __construct(
        #[Map(if: FailingHandler::class)]
        public ?string $name = null
    ) {}
}
