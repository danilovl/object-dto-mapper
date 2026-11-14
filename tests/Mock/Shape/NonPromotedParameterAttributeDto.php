<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class NonPromotedParameterAttributeDto
{
    public string $title;

    public function __construct(#[Map(source: 'name')] string $title)
    {
        $this->title = $title;
    }
}
