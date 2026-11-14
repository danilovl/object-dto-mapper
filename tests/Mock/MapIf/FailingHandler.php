<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use LogicException;

final class FailingHandler
{
    public function __invoke(mixed $value): bool
    {
        throw new LogicException('handler exploded');
    }
}
