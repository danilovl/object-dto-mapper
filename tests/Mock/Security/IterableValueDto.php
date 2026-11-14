<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Security;

final class IterableValueDto
{
    /**
     * @param iterable<mixed> $value
     */
    public function __construct(public iterable $value) {}
}
