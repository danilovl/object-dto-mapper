<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class EnumToScalarDto
{
    public function __construct(
        #[Map(source: 'status')]
        public string $statusValue,
        #[Map(source: 'priority')]
        public int $priorityValue,
        #[Map(source: 'priority')]
        public string $priorityAsString,
        #[Map(source: 'suit')]
        public string $suitName,
        #[Map(source: 'status')]
        public Status $sameEnum,
        #[Map(source: 'status')]
        public mixed $mixedEnum,
        #[Map(source: 'suit')]
        public Suit $sameUnitEnum
    ) {}
}
