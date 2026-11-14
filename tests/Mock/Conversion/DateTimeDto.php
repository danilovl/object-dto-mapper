<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

final class DateTimeDto
{
    /**
     * @param mixed $untyped
     */
    public function __construct(
        #[Map(source: 'immutable')]
        public string $asString,
        #[Map(source: 'immutable')]
        public DateTimeInterface $asInterface,
        #[Map(source: 'mutable')]
        public DateTimeImmutable $toImmutable,
        #[Map(source: 'immutable')]
        public DateTime $toMutable,
        #[Map(source: 'immutable')]
        public mixed $asMixed,
        #[Map(source: 'immutable')]
        public $untyped,
        #[Map(source: 'mutable')]
        public object $asObject,
        #[Map(source: 'immutable')]
        public ?string $asNullableString = null
    ) {}
}
