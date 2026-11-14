<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Group;

use Symfony\Component\Serializer\Attribute\Groups;

final class ParentDto
{
    public function __construct(
        #[Groups(['public'])]
        public string $name,
        #[Groups(['public'])]
        public ChildDto $child
    ) {}
}
