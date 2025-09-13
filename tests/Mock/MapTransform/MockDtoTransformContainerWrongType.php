<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\EmailNotEmptyMapIfService;

final class MockDtoTransformContainerWrongType
{
    public function __construct(
        public string $name,
        public int $age,
        #[Map(transformContainer: EmailNotEmptyMapIfService::class)]
        public ?string $email = null,
    ) {}
}
