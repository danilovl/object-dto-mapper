<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Group;

use Symfony\Component\Serializer\Attribute\Groups;

final class MultiGroupDto
{
    public function __construct(
        #[Groups(['public', 'list'])]
        public string $name,
        #[Groups(['admin', 'list'])]
        public ?int $age = null
    ) {}
}
