<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Collection;

final class ScalarIterableDto
{
    /**
     * @param iterable<mixed> $value
     */
    public function __construct(public iterable $value) {}
}
