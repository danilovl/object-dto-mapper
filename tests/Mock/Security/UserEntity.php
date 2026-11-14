<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Security;

final class UserEntity
{
    public function __construct(
        public string $login = 'bob',
        public string $passwordHash = '$2y$13$secret-hash'
    ) {}
}
