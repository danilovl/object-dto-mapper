<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\DTO;

use Danilovl\ObjectDtoMapper\Attribute\Map;

readonly class MockDtoWithCollection
{
    public function __construct(
        public string $title,
        #[Map(target: MockDto::class)]
        public array $items,
        public ?string $description = null
    ) {}
}
