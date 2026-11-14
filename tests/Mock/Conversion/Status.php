<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

enum Status: string
{
    case Active = 'active';
    case Blocked = 'blocked';
}
