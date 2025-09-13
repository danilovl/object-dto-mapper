<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Entity;

final class MockEntityWithAltNames
{
    public function __construct(
        public string $title,
        public int $count,
        public ?string $note
    ) {}
}
