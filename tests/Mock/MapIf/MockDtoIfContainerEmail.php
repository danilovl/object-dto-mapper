<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\EmailNotEmptyMapIfService;

final class MockDtoIfContainerEmail
{
    public function __construct(
        public string $name,
        public int $age,
        #[Map(ifContainer: EmailNotEmptyMapIfService::class)]
        public ?string $email = null,
    ) {}
}
