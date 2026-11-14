<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class MockDtoIfClosure
{
    public function __construct(
        public string $name,
        #[Map(if: static function (mixed $value, object $source): bool {
            return is_string($value) && str_contains($value, '@');
        })]
        public ?string $email = null
    ) {}
}
