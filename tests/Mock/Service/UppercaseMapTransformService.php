<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Service;

use Danilovl\ObjectDtoMapper\Attribute\MapTransformCallableInterface;

/**
 * @implements MapTransformCallableInterface<object>
 */
final class UppercaseMapTransformService implements MapTransformCallableInterface
{
    public function __invoke(mixed $value, object $source, string $target): mixed
    {
        if ($value === null) {
            return null;
        }

        return is_string($value) ? mb_strtoupper($value) : $value;
    }
}
