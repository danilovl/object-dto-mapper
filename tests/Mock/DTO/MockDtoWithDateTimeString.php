<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\DTO;

final class MockDtoWithDateTimeString
{
    public function __construct(
        public string $title,
        public string $happenedAt
    ) {}
}
