<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\{
    MockEntity,
    MockEntityWithFlags
};
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\{
    MockDtoIfBoolean,
    MockDtoIfCallable,
    MockDtoIfString,
    MockDtoIfStringNullable,
    MockDtoIfContainerEmail
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\{
    EmailNotEmptyMapIfService,
    BadMapIfService
};
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ObjectToDtoMapperAttributeIfTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $services = [
            EmailNotEmptyMapIfService::class => new EmailNotEmptyMapIfService,
            BadMapIfService::class => new BadMapIfService,
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

    public function testIfBooleanTrueAndFalseNullable(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');
        $result = $this->mapper->map($entity, MockDtoIfBoolean::class);

        $expected = new MockDtoIfBoolean('John Doe', null, 'john@example.com');

        $this->assertEquals($expected, $result);
    }

    public function testIfStringTrueMaps(): void
    {
        $entity = new MockEntityWithFlags('John Doe', 30, 'john@example.com', mapName: true);
        $result = $this->mapper->map($entity, MockDtoIfString::class);

        $expected = new MockDtoIfString('John Doe', 30, 'john@example.com');

        $this->assertEquals($expected, $result);
    }

    public function testIfStringFalseNullableBecomesNull(): void
    {
        $entity = new MockEntityWithFlags('John Doe', 30, 'john@example.com', mapName: false);
        $result = $this->mapper->map($entity, MockDtoIfStringNullable::class);

        $expected = new MockDtoIfStringNullable(null, 30, 'john@example.com');

        $this->assertEquals($expected, $result);
    }

    public function testIfCallableSkipsWhenEmpty(): void
    {
        $entity = new MockEntity('John Doe', 30, null);
        $result = $this->mapper->map($entity, MockDtoIfCallable::class);

        $expected = new MockDtoIfCallable('John Doe', 30, null);

        $this->assertEquals($expected, $result);
    }

    public function testIfCallableAllowsWhenNotEmpty(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');
        $result = $this->mapper->map($entity, MockDtoIfCallable::class);

        $expected = new MockDtoIfCallable('John Doe', 30, 'john@example.com');

        $this->assertEquals($expected, $result);
    }

    public function testIfContainerSkipsWhenEmpty(): void
    {
        $entity = new MockEntity('John Doe', 30, null);
        $result = $this->mapper->map($entity, MockDtoIfContainerEmail::class);

        $expected = new MockDtoIfContainerEmail('John Doe', 30, null);
        $this->assertEquals($expected, $result);
    }

    public function testIfContainerAllowsWhenNotEmpty(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');
        $result = $this->mapper->map($entity, MockDtoIfContainerEmail::class);

        $expected = new MockDtoIfContainerEmail('John Doe', 30, 'john@example.com');
        $this->assertEquals($expected, $result);
    }
}
