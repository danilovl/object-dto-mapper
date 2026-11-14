<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Security;

final class UserDto
{
    public function __construct(public string $login) {}
}
