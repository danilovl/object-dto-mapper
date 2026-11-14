<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

final class NullTransformer
{
    public function __invoke(mixed $value): mixed
    {
        return null;
    }
}
