<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Service;

use Danilovl\ObjectDtoMapper\Attribute\MapTransformCallableInterface;

/**
 * @implements MapTransformCallableInterface<object>
 */
final class TrimUppercaseMapTransformService implements MapTransformCallableInterface
{
    public function __invoke(mixed $value, object $source, string $target): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = mb_trim($value);

        return mb_strtoupper($trimmed);
    }
}
