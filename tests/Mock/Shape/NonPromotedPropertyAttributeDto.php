<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class NonPromotedPropertyAttributeDto
{
    #[Map(source: 'name')]
    public string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }
}
