<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Service;

use Danilovl\ObjectDtoMapper\Exception\MappingException;

interface ObjectToDtoMapperInterface
{
    /**
     * @template T of object
     * @param class-string<T> $target
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     * @return T
     * @throws MappingException
     */
    public function map(object $source, string $target, array $allowGroups = [], array $ignoreGroups = []): object;

    /**
     * @template T of object
     * @param iterable<mixed, object|null> $collection
     * @param class-string<T> $target
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     * @return array<array-key, T|null>
     * @throws MappingException
     */
    public function mapCollection(iterable $collection, string $target, array $allowGroups = [], array $ignoreGroups = []): array;
}
