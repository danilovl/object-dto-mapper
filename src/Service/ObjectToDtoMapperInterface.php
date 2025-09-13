<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Service;

use ReflectionException;

interface ObjectToDtoMapperInterface
{
    /**
     * @template T of object
     * @param object $source
     * @param class-string<T> $target
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     * @return T
     * @throws ReflectionException
     */
    public function map(object $source, string $target, array $allowGroups = [], array $ignoreGroups = []): object;
}
