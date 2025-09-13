<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Entity;

use DateTimeInterface;

final class MockEntityWithDateTime
{
    public function __construct(
        public string $title,
        public DateTimeInterface $happenedAt
    ) {}
}
