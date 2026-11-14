<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Group;

use Symfony\Component\Serializer\Attribute\Groups;

final class ChildDto
{
    public function __construct(
        #[Groups(['public'])]
        public string $city,
        #[Groups(['admin'])]
        public ?string $secret = null
    ) {}
}
