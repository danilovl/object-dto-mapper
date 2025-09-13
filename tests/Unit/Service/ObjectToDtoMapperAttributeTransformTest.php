<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\MockEntity;
use Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform\{
    MockDtoWithTransform,
    MockDtoWithTransformStringCallable
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\{
    TrimMapTransformService,
    TrimUppercaseMapTransformService,
    UppercaseMapTransformService
};
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ObjectToDtoMapperAttributeTransformTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $services = [
            UppercaseMapTransformService::class => new UppercaseMapTransformService,
            TrimMapTransformService::class => new TrimMapTransformService,
            TrimUppercaseMapTransformService::class => new TrimUppercaseMapTransformService,
        ];

        $container = new class($services) implements ContainerInterface {
            /** @var array<string, object> */
            public array $services;

            public function __construct(array $services)
            {
                $this->services = $services;
            }

            public function get(string $id)
            {
                if (!$this->has($id)) {
                    throw new class ( 'Service not found: ' . $id ) extends RuntimeException {};
                }

                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->services);
            }
        };

        $this->mapper = new ObjectToDtoMapper($container);
    }

    public function testTransformInvokableFromContainer(): void
    {
        $entity = new MockEntity('john', 30, 'john@example.com');

        $result = $this->mapper->map($entity, MockDtoWithTransform::class);

        $this->assertSame('JOHN', $result->name);
        $this->assertSame(30, $result->age);
        $this->assertSame('JOHN@EXAMPLE.COM', $result->email);
    }

    public function testTransformChainMultiple(): void
    {
        $entity = new MockEntity('john', 30, '  john@example.com  ');

        $result = $this->mapper->map($entity, MockDtoWithTransform::class);

        $this->assertSame('JOHN', $result->name);
        $this->assertSame('JOHN@EXAMPLE.COM', $result->email);
    }

    public function testTransformWithCallableString(): void
    {
        $entity = new MockEntity('  John  ', 20, '  john@example.com  ');
        $result = $this->mapper->map($entity, MockDtoWithTransformStringCallable::class);

        $this->assertSame('  John  ', $result->name);
        $this->assertSame(20, $result->age);
        $this->assertSame('john@example.com', $result->email);
    }
}
