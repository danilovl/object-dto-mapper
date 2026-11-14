<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Service;

use Psr\Container\ContainerInterface;
use RuntimeException;

final class ArrayContainer implements ContainerInterface
{
    /**
     * @var array<string, int>
     */
    public array $getCalls = [];

    /**
     * @param array<string, object> $services
     * @param string[] $brokenServices ids that exist but throw on get()
     */
    public function __construct(private readonly array $services = [], private readonly array $brokenServices = []) {}

    public function get(string $id): object
    {
        $this->getCalls[$id] = ($this->getCalls[$id] ?? 0) + 1;

        if (in_array($id, $this->brokenServices, true)) {
            throw new RuntimeException('Service is broken: ' . $id);
        }

        if (!array_key_exists($id, $this->services)) {
            throw new ServiceNotFoundException('Service not found: ' . $id);
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services) || in_array($id, $this->brokenServices, true);
    }
}
