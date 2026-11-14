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
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\MockDtoIfContainerWrongType;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\{
    ArrayContainer,
    EmailNotEmptyMapIfService,
    UppercaseMapTransformService
};
use PHPUnit\Framework\TestCase;

final class ObjectToDtoMapperExceptionTest extends TestCase
{
    private ObjectToDtoMapper $mapperWithContainer;

    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $container = new ArrayContainer([
            EmailNotEmptyMapIfService::class => new EmailNotEmptyMapIfService,
            UppercaseMapTransformService::class => new UppercaseMapTransformService,
        ]);

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

    public function testIfContainerWithoutConfiguredContainerThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage(sprintf('Map(if) container service not found: "%s" (no container configured in the mapper)', EmailNotEmptyMapIfService::class));

        $this->mapper->map(new MockEntity('John', 20, 'a@a.a'), \Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\MockDtoIfContainerEmail::class);
    }

    public function testIfContainerWrongServiceTypeThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage(sprintf('Map(if) container "%s" is not a valid callable service', UppercaseMapTransformService::class));

        $this->mapperWithContainer->map(new MockEntity('John', 20), MockDtoIfContainerWrongType::class);
    }

    public function testNonNullablePropertyReceivesNullFromNestedPathContainsPath(): void
    {
        $entity = new \Danilovl\ObjectDtoMapper\Tests\Mock\Entity\MockNestedEntity('Parent', new MockEntity('Child', 1, null));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('(path: "entity.email")');

        $this->mapper->map($entity, \Danilovl\ObjectDtoMapper\Tests\Mock\Exception\NestedNotNullableDto::class);
    }
}
