<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Attribute;

/**
 * @template T of object
 */
interface MapIfCallableInterface
{
    /**
     * @param T $source The object we're working on
     */
    public function __invoke(mixed $value, object $source, string $target): bool;
}
