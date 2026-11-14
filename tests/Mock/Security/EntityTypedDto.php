<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Security;

final class EntityTypedDto
{
    public function __construct(public UserEntity $value) {}
}
