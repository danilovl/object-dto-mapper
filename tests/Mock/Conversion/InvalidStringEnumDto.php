<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class InvalidStringEnumDto
{
    public function __construct(
        #[Map(source: 'invalidCode')]
        public Status $status
    ) {}
}
