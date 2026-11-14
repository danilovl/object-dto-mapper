<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Group;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\FailingHandler;
use Symfony\Component\Serializer\Attribute\Groups;

final class GroupedIfDto
{
    public function __construct(
        public string $name,
        #[Groups(['admin'])]
        #[Map(if: FailingHandler::class)]
        public ?string $email = null
    ) {}
}
