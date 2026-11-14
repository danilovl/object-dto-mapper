<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\EmailNotEmptyMapIfService;

final class MockDtoIfContainerFalseNonNullable
{
    public function __construct(
        #[Map(ifContainer: EmailNotEmptyMapIfService::class)]
        public string $email
    ) {}
}
