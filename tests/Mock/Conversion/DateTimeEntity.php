<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use DateTime;
use DateTimeImmutable;

final class DateTimeEntity
{
    public function __construct(
        public DateTimeImmutable $immutable,
        public DateTime $mutable
    ) {}
}
