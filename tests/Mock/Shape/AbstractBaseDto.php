<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

use Danilovl\ObjectDtoMapper\Attribute\Map;

abstract class AbstractBaseDto
{
    public function __construct(
        private string $name,
        #[Map(source: 'age')]
        protected int $years
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getYears(): int
    {
        return $this->years;
    }
}
