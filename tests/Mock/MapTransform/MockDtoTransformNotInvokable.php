<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\NotInvokable;

final class MockDtoTransformNotInvokable
{
    public function __construct(
        #[Map(transform: NotInvokable::class)]
        public string $name
    ) {}
}
