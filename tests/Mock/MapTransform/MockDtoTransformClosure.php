<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoTransformClosure
{
    public function __construct(
        #[Map(transform: static function (string $value): string {
            return mb_strtoupper($value);
        })]
        public string $name,
        #[Map(transform: mb_strtolower(...))]
        public ?string $email = null
    ) {}
}
