<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Metadata;

use ReflectionClass;

/**
 * @template T of object
 */
final readonly class ClassMetadata
{
    /**
     * @param class-string<T> $class
     * @param ReflectionClass<T> $reflection
     * @param list<ParameterMetadata> $parameters
     */
    public function __construct(
        public string $class,
        public ReflectionClass $reflection,
        public array $parameters
    ) {}
}
