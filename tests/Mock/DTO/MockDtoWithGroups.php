<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\DTO;

use Symfony\Component\Serializer\Attribute\Groups;

readonly class MockDtoWithGroups
{
    public function __construct(
        #[Groups(['public'])]
        public string $name,
        #[Groups(['admin'])]
        public int $age,
        #[Groups(['private'])]
        public ?string $email = null
    ) {}
}
