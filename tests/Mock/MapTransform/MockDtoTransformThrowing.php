<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\FailingHandler;

final class MockDtoTransformThrowing
{
    public function __construct(
        #[Map(transform: FailingHandler::class)]
        public string $name
    ) {}
}
