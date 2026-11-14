<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Group;

final class ChildEntity
{
    public function __construct(
        public string $city,
        public string $secret
    ) {}
}
