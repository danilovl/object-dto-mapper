<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

final class SourceAwareTransformer
{
    public function __invoke(mixed $value, object $source, string $target): string
    {
        return sprintf('%s|%s|%s', is_scalar($value) ? (string) $value : '', $source::class, $target);
    }
}
