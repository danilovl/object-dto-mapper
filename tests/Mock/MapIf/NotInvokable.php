<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

final class NotInvokable
{
    public function check(mixed $value): bool
    {
        return true;
    }
}
