<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Collection;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MissingTargetDto
{
    public function __construct(
        /**
         * @phpstan-ignore argument.type
         */
        #[Map(target: 'Danilovl\\ObjectDtoMapper\\Tests\\Missing\\ItemDto')]
        public array $value
    ) {}
}
