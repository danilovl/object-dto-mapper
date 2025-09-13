<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\BadMapIfService;

final class MockDtoIfContainerBad
{
    public function __construct(
        #[Map(ifContainer: BadMapIfService::class)]
        public string $name,
        public int $age,
        public ?string $email = null,
    ) {}
}
