<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use DateTimeImmutable;

final class MockDtoTransformToDateTime
{
    public function __construct(
        #[Map(transform: static function (): DateTimeImmutable {
            return new DateTimeImmutable('2021-05-06T07:08:09+00:00');
        })]
        public string $name
    ) {}
}
