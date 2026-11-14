<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class DefaultObjectDto
{
    public function __construct(
        public string $name,
        public Options $options = new Options
    ) {}
}
