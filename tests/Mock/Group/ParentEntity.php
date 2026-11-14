<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Group;

final class ParentEntity
{
    public function __construct(
        public string $name,
        public ChildEntity $child
    ) {}
}
