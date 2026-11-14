<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\MapIf;

final class ArgumentRecorder
{
    /**
     * @var list<array<array-key, mixed>>
     */
    public static array $calls = [];

    public static function record(mixed ...$args): bool
    {
        self::$calls[] = $args;

        return true;
    }

    public static function recordTwo(mixed $value, object $source): bool
    {
        self::$calls[] = [$value, $source];

        return true;
    }
}
