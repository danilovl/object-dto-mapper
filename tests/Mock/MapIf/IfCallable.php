<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

final class IfCallable
{
    public static function useFlagMapName(mixed $value, object $entity): bool
    {
        /** @var object{mapName?:bool} $entity */
        return (bool) ($entity->mapName ?? false);
    }

    public static function allowIfValueNotEmpty(mixed $value, object $entity): bool
    {
        return !empty($value);
    }

    public static function denyIfAgeLessThan(mixed $value, object $entity): bool
    {
        /** @var object{age:int} $entity */
        return $entity->age >= 18;
    }
}
