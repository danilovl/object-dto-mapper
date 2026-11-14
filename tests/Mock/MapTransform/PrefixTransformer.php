<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform;

final class PrefixTransformer
{
    public function __construct(private readonly string $prefix) {}

    public function __invoke(mixed $value): string
    {
        return $this->prefix . (is_scalar($value) ? (string) $value : '');
    }
}
