<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class TagsEntity
{
    /**
     * @param string[]|string|null $tags
     */
    public function __construct(
        public string $name,
        public array|string|null $tags
    ) {}
}
