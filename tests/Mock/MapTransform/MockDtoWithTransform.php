<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\{
    TrimUppercaseMapTransformService,
    UppercaseMapTransformService
};

final class MockDtoWithTransform
{
    public function __construct(
        #[Map(transformContainer: UppercaseMapTransformService::class)]
        public string $name,
        public int $age,
        #[Map(transform: TrimUppercaseMapTransformService::class)]
        public ?string $email = null
    ) {}
}
