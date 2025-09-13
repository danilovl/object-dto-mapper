<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Exception\{
    MappingIfException,
    MappingTransformException,
    NoSuchClassException,
    RuntimeException
};
use Danilovl\ObjectDtoMapper\Tests\Mock\DTO\{
    MockDtoEmailNotNullable,
    MockDtoWithoutConstructor
};
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\{
    MockDtoIfBooleanException,
    MockDtoIfCallableStringBuiltin,
    MockDtoIfContainerNotFound,
    MockDtoIfInvalidClassString
};
use Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform\{
    MockDtoTransformContainerNotFound,
    MockDtoTransformContainerWrongType
};
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\MockEntity;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\EmailNotEmptyMapIfService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException as PhpRuntimeException;

final class ObjectToDtoMapperExceptionTest extends TestCase
{
    private ObjectToDtoMapper $mapperWithContainer;

    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $services = [
            EmailNotEmptyMapIfService::class => new EmailNotEmptyMapIfService,
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
                    throw new class ( 'Service not found: ' . $id ) extends PhpRuntimeException {};
                }

                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->services);
            }
        };

        $this->mapperWithContainer = new ObjectToDtoMapper($container);
        $this->mapper = new ObjectToDtoMapper;
    }

    public function testConstructorException(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');

        $this->expectException(RuntimeException::class);
        $message = sprintf('Target class must have a constructor: %s', MockDtoWithoutConstructor::class);
        $this->expectExceptionMessage($message);

        $this->mapper->map($entity, MockDtoWithoutConstructor::class);
    }

    public function testIfBooleanFalseNonNullableThrows(): void
    {
        $expected = new MockEntity('John Doe', 30, 'john@example.com');

        $this->expectException(MappingIfException::class);
        $message = sprintf('Non-nullable property "%s" of "%s" skipped by Map(if) condition', 'age', MockDtoIfBooleanException::class);
        $this->expectExceptionMessage($message);

        $this->mapper->map($expected, MockDtoIfBooleanException::class);
    }

    public function testTargetClassDoesNotExistThrows(): void
    {
        $entity = new MockEntity('John', 20, null);

        $this->expectException(NoSuchClassException::class);
        $message = sprintf('Target class must exist: %s', '\\Some\\Missing\\Class');
        $this->expectExceptionMessage($message);

        /** @phpstan-ignore-next-line */
        $this->mapper->map($entity, '\\Some\\Missing\\Class');
    }

    public function testNonNullablePropertyReceivesNullThrows(): void
    {
        $entity = new MockEntity('John', 20, null);

        $this->expectException(RuntimeException::class);
        $message = sprintf('Non-nullable property "%s" of "%s" is null', 'email', MockDtoEmailNotNullable::class);
        $this->expectExceptionMessage($message);

        $this->mapper->map($entity, MockDtoEmailNotNullable::class);
    }

    public function testIfCallableStringBuiltinWorks(): void
    {
        $entity = new MockEntity('John', 20, 'a');
        $result = $this->mapper->map($entity, MockDtoIfCallableStringBuiltin::class);

        $this->assertSame('John', $result->name);
        $this->assertSame(20, $result->age);
        $this->assertSame('a', $result->email);
    }

    public function testIfContainerServiceNotFoundThrows(): void
    {
        $entity = new MockEntity('John', 20, null);

        $this->expectException(MappingIfException::class);
        $message = sprintf('Map(if) container service not found: "%s"', 'not.existing.service');
        $this->expectExceptionMessage($message);

        $this->mapperWithContainer->map($entity, MockDtoIfContainerNotFound::class);
    }

    public function testIfInvalidClassStringThrowsNoSuchClassException(): void
    {
        $entity = new MockEntity('John', 20, 'john@example.com');

        $this->expectException(NoSuchClassException::class);
        $message = sprintf('Map(%s) definition error: Class "%s" does not exist', 'if', '\\Invalid\\MissingCallable');
        $this->expectExceptionMessage($message);

        $this->mapper->map($entity, MockDtoIfInvalidClassString::class);
    }

    public function testTransformContainerServiceNotFoundThrows(): void
    {
        $entity = new MockEntity('John', 20, ' john@example.com ');

        $this->expectException(MappingTransformException::class);
        $message = sprintf('Map(transformContainer) service not found: "%s"', 'not.existing.transform');
        $this->expectExceptionMessage($message);

        $this->mapperWithContainer->map($entity, MockDtoTransformContainerNotFound::class);
    }

    public function testTransformContainerWrongServiceTypeThrows(): void
    {
        $entity = new MockEntity('John', 20, ' john@example.com ');

        $this->expectException(MappingTransformException::class);
        $message = sprintf('Map(transformContainer) "%s" is not a valid callable service', EmailNotEmptyMapIfService::class);
        $this->expectExceptionMessage($message);

        $this->mapperWithContainer->map($entity, MockDtoTransformContainerWrongType::class);
    }
}
