<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Service;

use Danilovl\ObjectDtoMapper\Attribute\MapIfCallableInterface;

/**
 * @implements MapIfCallableInterface<object>
 */
final class BadMapIfService implements MapIfCallableInterface
{
    public function __invoke(mixed $value, object $source, string $target): bool
    {
        return true;
    }
}
