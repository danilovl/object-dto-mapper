<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Exception\{
    MappingTransformException,
    NoSuchClassException,
    RuntimeException
};
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\MockEntity;
use Danilovl\ObjectDtoMapper\Tests\Mock\MapTransform\{
    MockDtoTransformClosure,
    MockDtoTransformContainerBroken,
    MockDtoTransformFromContainerClassString,
    MockDtoTransformMissingClass,
    MockDtoTransformNotInvokable,
    MockDtoTransformSourceAware,
    MockDtoTransformThrowing,
    MockDtoTransformToDateTime,
    MockDtoTransformToNull,
    MockDtoWithTransform,
    MockDtoWithTransformStringCallable,
    PrefixTransformer
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\{
    ArrayContainer,
    TrimMapTransformService,
    TrimUppercaseMapTransformService,
    UppercaseMapTransformService
};
use LogicException;
use PHPUnit\Framework\TestCase;

class ObjectToDtoMapperAttributeTransformTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $container = new ArrayContainer(
            [
                UppercaseMapTransformService::class => new UppercaseMapTransformService,
                TrimMapTransformService::class => new TrimMapTransformService,
                TrimUppercaseMapTransformService::class => new TrimUppercaseMapTransformService,
                PrefixTransformer::class => new PrefixTransformer('prefix-'),
            ],
            ['broken.service']
        );

        $this->mapper = new ObjectToDtoMapper($container);
    }

    public function testTransformInvokableFromContainer(): void
    {
        $result = $this->mapper->map(new MockEntity('john', 30, 'john@example.com'), MockDtoWithTransform::class);

        $this->assertSame('JOHN', $result->name);
        $this->assertSame(30, $result->age);
        $this->assertSame('JOHN@EXAMPLE.COM', $result->email);
    }

    public function testTransformComposedInSingleTransformer(): void
    {
        $result = $this->mapper->map(new MockEntity('john', 30, '  john@example.com  '), MockDtoWithTransform::class);

        $this->assertSame('JOHN', $result->name);
        $this->assertSame('JOHN@EXAMPLE.COM', $result->email);
    }

    public function testTransformWithBuiltinFunctionReceivesOnlyValue(): void
    {
        $result = $this->mapper->map(new MockEntity('  John  ', 20, '  john@example.com  '), MockDtoWithTransformStringCallable::class);

        $this->assertSame('  John  ', $result->name);
        $this->assertSame('john@example.com', $result->email);
    }

    public function testTransformClosureAndFirstClassCallableInAttribute(): void
    {
        $result = $this->mapper->map(new MockEntity('john', 20, 'JOHN@EXAMPLE.COM'), MockDtoTransformClosure::class);

        $this->assertSame('JOHN', $result->name);
        $this->assertSame('john@example.com', $result->email);
    }

    public function testTransformNullValueIsPassedToHandlerAndBuiltinErrorIsWrapped(): void
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage('mb_strtolower(): Argument #1 ($string) must be of type string, null given (path: "email")');

        $this->mapper->map(new MockEntity('john', 20, null), MockDtoTransformClosure::class);
    }

    public function testTransformClassStringIsTakenFromContainer(): void
    {
        $result = $this->mapper->map(new MockEntity('john', 20), MockDtoTransformFromContainerClassString::class);

        $this->assertSame('prefix-john', $result->name);
    }

    public function testTransformClassWithConstructorArgumentsWithoutContainerThrows(): void
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage(sprintf('Map(transform) definition "%s" for property name cannot be instantiated without arguments', PrefixTransformer::class));

        new ObjectToDtoMapper()->map(new MockEntity('john', 20), MockDtoTransformFromContainerClassString::class);
    }

    public function testTransformReceivesSourceAndTarget(): void
    {
        $result = $this->mapper->map(new MockEntity('john', 20), MockDtoTransformSourceAware::class);

        $this->assertSame(sprintf('john|%s|%s', MockEntity::class, MockDtoTransformSourceAware::class), $result->name);
    }

    public function testTransformResultIsConverted(): void
    {
        $result = $this->mapper->map(new MockEntity('john', 20), MockDtoTransformToDateTime::class);

        $this->assertSame('2021-05-06T07:08:09+00:00', $result->name);
    }

    public function testTransformToNullForNonNullableThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Non-nullable property "name" of "%s" is null (path: "name")', MockDtoTransformToNull::class));

        $this->mapper->map(new MockEntity('john', 20), MockDtoTransformToNull::class);
    }

    public function testTransformHandlerExceptionIsWrapped(): void
    {
        try {
            $this->mapper->map(new MockEntity('john', 20), MockDtoTransformThrowing::class);
            $this->fail('Exception expected');
        } catch (MappingTransformException $exception) {
            $this->assertStringStartsWith('Map(transform) handler failed for property "name"', $exception->getMessage());
            $this->assertInstanceOf(LogicException::class, $exception->getPrevious());
        }
    }

    public function testTransformNotInvokableThrowsTransformException(): void
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage('Map(transform) definition');

        $this->mapper->map(new MockEntity('john', 20), MockDtoTransformNotInvokable::class);
    }

    public function testTransformMissingClassThrows(): void
    {
        $this->expectException(NoSuchClassException::class);
        $this->expectExceptionMessage('Map(transform) definition error: Class "Danilovl\Missing\Transformer" does not exist for property name');

        $this->mapper->map(new MockEntity('john', 20), MockDtoTransformMissingClass::class);
    }

    public function testTransformContainerServiceThrowingOnGetIsWrapped(): void
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage('Service "broken.service" cannot be retrieved from container: Service is broken: broken.service');

        $this->mapper->map(new MockEntity('john', 20), MockDtoTransformContainerBroken::class);
    }
}
