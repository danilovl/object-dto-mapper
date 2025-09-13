<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Entity;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\DTO\MockDto;

readonly class MockEntityWithCollection
{
    public function __construct(
        public string $title,
        #[Map(target: MockDto::class)]
        public array $items,
        public ?string $description = null
    ) {}
}
