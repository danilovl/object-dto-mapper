<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class VariadicDto
{
    /**
     * @var string[]
     */
    public array $tags;

    public function __construct(public string $name, string ...$tags)
    {
        $this->tags = $tags;
    }
}
