<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Service;

use Danilovl\ObjectDtoMapper\Attribute\MapIfCallableInterface;

/**
 * @implements MapIfCallableInterface<object>
 */
final class AgeAtLeast18MapIfService implements MapIfCallableInterface
{
    public function __invoke(mixed $value, object $source, string $target): bool
    {
        /** @var object{age:int} $source */
        return $source->age >= 18;
    }
}
