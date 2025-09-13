<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Service;

use Danilovl\ObjectDtoMapper\Attribute\MapTransformCallableInterface;

/**
 * @implements MapTransformCallableInterface<object>
 */
final class TrimMapTransformService implements MapTransformCallableInterface
{
    public function __invoke(mixed $value, object $source, string $target): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return mb_trim($value);
    }
}
