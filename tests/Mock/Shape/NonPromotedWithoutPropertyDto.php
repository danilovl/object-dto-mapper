<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class NonPromotedWithoutPropertyDto
{
    public string $upperName;

    public function __construct(string $name)
    {
        $this->upperName = mb_strtoupper($name);
    }
}
