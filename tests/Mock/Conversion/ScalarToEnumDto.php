<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class ScalarToEnumDto
{
    public function __construct(
        #[Map(source: 'statusCode')]
        public Status $status,
        #[Map(source: 'priorityCode')]
        public Priority $priority,
        #[Map(source: 'priorityString')]
        public Priority $priorityFromString,
        #[Map(source: 'statusCode')]
        public Status|string $statusOrString,
        #[Map(source: 'nullableCode')]
        public ?Status $nullable = null
    ) {}
}
