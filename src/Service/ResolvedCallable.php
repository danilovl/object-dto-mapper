<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Service;

use Closure;
use ReflectionFunction;

final readonly class ResolvedCallable
{
    private int $arity;

    public function __construct(public Closure $closure)
    {
        $reflection = new ReflectionFunction($closure);

        $this->arity = match (true) {
            // extra args would become optional parameters of built-ins, e.g. trim() characters
            $reflection->isInternal() => 1,
            $reflection->isVariadic() => 3,
            default => min(3, $reflection->getNumberOfParameters())
        };
    }

    public function __invoke(mixed $value, object $source, string $target): mixed
    {
        $args = array_slice([$value, $source, $target], 0, $this->arity);

        return ($this->closure)(...$args);
    }
}
