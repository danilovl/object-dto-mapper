<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\UppercaseMapTransformService;

final class MockDtoIfContainerWrongType
{
    public function __construct(
        #[Map(ifContainer: UppercaseMapTransformService::class)]
        public ?string $name = null
    ) {}
}
