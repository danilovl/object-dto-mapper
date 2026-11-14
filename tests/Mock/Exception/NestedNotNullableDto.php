<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Exception;

use Danilovl\ObjectDtoMapper\Tests\Mock\DTO\MockDtoEmailNotNullable;

final class NestedNotNullableDto
{
    public function __construct(
        public string $name,
        public MockDtoEmailNotNullable $entity
    ) {}
}
