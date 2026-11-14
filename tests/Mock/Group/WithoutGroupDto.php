<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Group;

use Symfony\Component\Serializer\Attribute\Groups;

final class WithoutGroupDto
{
    public function __construct(
        #[Groups(['public'])]
        public string $name,
        public ?int $age = null
    ) {}
}
