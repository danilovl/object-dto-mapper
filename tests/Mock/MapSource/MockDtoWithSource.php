<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapSource;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final readonly class MockDtoWithSource
{
    public function __construct(
        #[Map(source: 'title')]
        public string $name,
        #[Map(source: 'count')]
        public int $age,
        #[Map(source: 'note')]
        public ?string $email
    ) {}
}
