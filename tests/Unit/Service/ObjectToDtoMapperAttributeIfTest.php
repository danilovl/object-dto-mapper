<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Exception\MappingIfException;
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\{
    MockEntity,
    MockEntityWithFlags
};
use Danilovl\ObjectDtoMapper\Tests\Mock\MapIf\{
    ArgumentRecorder,
    MockDtoIfBoolean,
    MockDtoIfCallable,
    MockDtoIfCallableStringBuiltin,
    MockDtoIfClosure,
    MockDtoIfContainerEmail,
    MockDtoIfContainerFalseNonNullable,
    MockDtoIfFalseWithDefault,
    MockDtoIfNonStaticMethod,
    MockDtoIfNotInvokable,
    MockDtoIfStaticMethodString,
    MockDtoIfString,
    MockDtoIfStringNullable,
    MockDtoIfThrowing,
    MockDtoIfTwoArgsRecorder,
    MockDtoIfVariadicRecorder
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\{
    ArrayContainer,
    BadMapIfService,
    EmailNotEmptyMapIfService
};
use LogicException;
use PHPUnit\Framework\TestCase;

class ObjectToDtoMapperAttributeIfTest extends TestCase
{
    private ArrayContainer $container;

    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $this->container = new ArrayContainer([
            EmailNotEmptyMapIfService::class => new EmailNotEmptyMapIfService,
            BadMapIfService::class => new BadMapIfService,
        ]);

        $this->mapper = new ObjectToDtoMapper($this->container);
        ArgumentRecorder::$calls = [];
    }

    public function testIfBooleanTrueAndFalseNullable(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');
        $result = $this->mapper->map($entity, MockDtoIfBoolean::class);

        $this->assertEquals(new MockDtoIfBoolean('John Doe', null, 'john@example.com'), $result);
    }

    public function testIfFalseUsesDefaultValue(): void
    {
        $result = $this->mapper->map(new MockEntity('John Doe', 30), MockDtoIfFalseWithDefault::class);

        $this->assertSame('hidden', $result->name);
    }

    public function testIfStringTrueMaps(): void
    {
        $entity = new MockEntityWithFlags('John Doe', 30, 'john@example.com', mapName: true);
        $result = $this->mapper->map($entity, MockDtoIfString::class);

        $this->assertEquals(new MockDtoIfString('John Doe', 30, 'john@example.com'), $result);
    }

    public function testIfStringFalseNullableBecomesNull(): void
    {
        $entity = new MockEntityWithFlags('John Doe', 30, 'john@example.com', mapName: false);
        $result = $this->mapper->map($entity, MockDtoIfStringNullable::class);

        $this->assertEquals(new MockDtoIfStringNullable(null, 30, 'john@example.com'), $result);
    }

    public function testIfStringFalseNonNullableThrows(): void
    {
        $entity = new MockEntityWithFlags('John Doe', 30, 'john@example.com', mapName: false);

        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage(sprintf('Non-nullable property "name" of "%s" skipped by Map(if) condition (path: "name")', MockDtoIfString::class));

        $this->mapper->map($entity, MockDtoIfString::class);
    }

    public function testIfCallableSkipsWhenEmpty(): void
    {
        $result = $this->mapper->map(new MockEntity('John Doe', 30, null), MockDtoIfCallable::class);

        $this->assertEquals(new MockDtoIfCallable('John Doe', 30, null), $result);
    }

    public function testIfCallableAllowsWhenNotEmpty(): void
    {
        $result = $this->mapper->map(new MockEntity('John Doe', 30, 'john@example.com'), MockDtoIfCallable::class);

        $this->assertEquals(new MockDtoIfCallable('John Doe', 30, 'john@example.com'), $result);
    }

    public function testIfBuiltinFunctionResultIsCastToBool(): void
    {
        $this->assertSame('John', $this->mapper->map(new MockEntity('John', 20), MockDtoIfCallableStringBuiltin::class)->name);
        $this->assertNull($this->mapper->map(new MockEntity('', 20), MockDtoIfCallableStringBuiltin::class)->name);
    }

    public function testIfStaticMethodString(): void
    {
        $this->assertSame('a@b.c', $this->mapper->map(new MockEntity('John', 1, 'a@b.c'), MockDtoIfStaticMethodString::class)->email);
        $this->assertNull($this->mapper->map(new MockEntity('John', 1, ''), MockDtoIfStaticMethodString::class)->email);
    }

    public function testIfClosureInAttribute(): void
    {
        $this->assertSame('john@example.com', $this->mapper->map(new MockEntity('John', 1, 'john@example.com'), MockDtoIfClosure::class)->email);
        $this->assertNull($this->mapper->map(new MockEntity('John', 1, 'not-an-email'), MockDtoIfClosure::class)->email);
    }

    public function testIfContainerSkipsWhenEmpty(): void
    {
        $result = $this->mapper->map(new MockEntity('John Doe', 30, null), MockDtoIfContainerEmail::class);

        $this->assertEquals(new MockDtoIfContainerEmail('John Doe', 30, null), $result);
    }

    public function testIfContainerAllowsWhenNotEmpty(): void
    {
        $result = $this->mapper->map(new MockEntity('John Doe', 30, 'john@example.com'), MockDtoIfContainerEmail::class);

        $this->assertEquals(new MockDtoIfContainerEmail('John Doe', 30, 'john@example.com'), $result);
    }

    public function testIfContainerFalseNonNullableThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage('skipped by Map(if) condition');

        $this->mapper->map(new MockEntity('John Doe', 30, ''), MockDtoIfContainerFalseNonNullable::class);
    }

    public function testIfContainerServiceIsResolvedOnce(): void
    {
        $this->mapper->map(new MockEntity('A', 1, 'a@a.a'), MockDtoIfContainerEmail::class);
        $this->mapper->map(new MockEntity('B', 1, 'b@b.b'), MockDtoIfContainerEmail::class);

        $this->assertSame(1, $this->container->getCalls[EmailNotEmptyMapIfService::class]);
    }

    public function testHandlerReceivesValueSourceAndTarget(): void
    {
        $entity = new MockEntity('John', 1);

        $this->mapper->map($entity, MockDtoIfVariadicRecorder::class);
        $this->mapper->map($entity, MockDtoIfTwoArgsRecorder::class);

        $this->assertSame([
            ['John', $entity, MockDtoIfVariadicRecorder::class],
            ['John', $entity]
        ], ArgumentRecorder::$calls);
    }

    public function testNonStaticMethodArrayThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage('Map(if) definition for property "name" is not a valid callable');

        $this->mapper->map(new MockEntity('John', 1), MockDtoIfNonStaticMethod::class);
    }

    public function testNotInvokableClassThrows(): void
    {
        $this->expectException(MappingIfException::class);
        $this->expectExceptionMessage('is not a valid callable for property name');

        $this->mapper->map(new MockEntity('John', 1), MockDtoIfNotInvokable::class);
    }

    public function testHandlerExceptionIsWrapped(): void
    {
        try {
            $this->mapper->map(new MockEntity('John', 1), MockDtoIfThrowing::class);
            $this->fail('Exception expected');
        } catch (MappingIfException $exception) {
            $this->assertSame(
                sprintf('Map(if) handler failed for property "name" of "%s": handler exploded (path: "name")', MockDtoIfThrowing::class),
                $exception->getMessage()
            );
            $this->assertInstanceOf(LogicException::class, $exception->getPrevious());
        }
    }
}
