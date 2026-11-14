<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Collection;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class ListDto
{
    /**
     * @param array<array-key, ItemDto|null> $value
     */
    public function __construct(
        #[Map(target: ItemDto::class)]
        public array $value
    ) {}
}
