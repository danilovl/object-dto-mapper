<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use ArrayIterator;
use Danilovl\ObjectDtoMapper\Exception\UnmappedObjectException;
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Conversion\Status;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\Holder;
use Danilovl\ObjectDtoMapper\Tests\Mock\Security\{
    ArrayValueDto,
    EntityTypedDto,
    IterableValueDto,
    MixedValueDto,
    ObjectValueDto,
    TransformedObjectDto,
    UntypedValueDto,
    UserEntity
};
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ObjectToDtoMapperSecurityTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ObjectToDtoMapper;
    }

    /**
     * @param class-string $dtoClass
     */
    #[DataProvider('provideUnmappedObjectIsRejectedCases')]
    public function testUnmappedObjectIsRejected(mixed $value, string $dtoClass, string $expectedMessagePart): void
    {
        $this->expectException(UnmappedObjectException::class);
        $this->expectExceptionMessage($expectedMessagePart);

        $this->mapper->map(new Holder($value), $dtoClass);
    }

    public static function provideUnmappedObjectIsRejectedCases(): Generator
    {
        $prefix = sprintf('Refusing to pass unmapped object of class "%s" into property "value"', UserEntity::class);

        yield 'entity in array' => [[new UserEntity], ArrayValueDto::class, $prefix];
        yield 'entity in nested array' => [['users' => ['admin' => new UserEntity]], ArrayValueDto::class, 'at key "users.admin"'];
        yield 'entities in traversable to array' => [new ArrayIterator([new UserEntity]), ArrayValueDto::class, $prefix];
        yield 'entities in traversable to iterable' => [new ArrayIterator([new UserEntity]), IterableValueDto::class, $prefix];
        yield 'entity to mixed' => [new UserEntity, MixedValueDto::class, $prefix];
        yield 'entity to object' => [new UserEntity, ObjectValueDto::class, $prefix];
        yield 'entity to untyped' => [new UserEntity, UntypedValueDto::class, $prefix];
        yield 'entity array to mixed' => [[new UserEntity], MixedValueDto::class, $prefix];
        yield 'stdClass to object' => [new stdClass, ObjectValueDto::class, 'Refusing to pass unmapped object of class "stdClass"'];
    }

    /**
     * @param class-string $dtoClass
     */
    #[DataProvider('provideSafeValuesArePassedCases')]
    public function testSafeValuesArePassed(mixed $value, string $dtoClass): void
    {
        $result = $this->mapper->map(new Holder($value), $dtoClass);

        /** @var object{value: mixed} $result */
        $this->assertEquals($value, $result->value);
    }

    public static function provideSafeValuesArePassedCases(): Generator
    {
        yield 'scalars in array' => [[1, 'a', null, true, 1.5], ArrayValueDto::class];
        yield 'enum and date in array' => [[Status::Active, new DateTimeImmutable('2020-01-01')], ArrayValueDto::class];
        yield 'enum to mixed' => [Status::Active, MixedValueDto::class];
        yield 'enum to object' => [Status::Active, ObjectValueDto::class];
        yield 'scalar to mixed' => ['string', MixedValueDto::class];
    }

    public function testExplicitlyTypedEntityIsPassedAsIs(): void
    {
        $entity = new UserEntity;

        $result = $this->mapper->map(new Holder($entity), EntityTypedDto::class);

        $this->assertSame($entity, $result->value);
    }

    public function testTransformResultIsTrusted(): void
    {
        $result = $this->mapper->map(new Holder(new UserEntity), TransformedObjectDto::class);

        $this->assertInstanceOf(stdClass::class, $result->value);
    }

    public function testAllowObjectPassthroughDisablesTheCheck(): void
    {
        $mapper = new ObjectToDtoMapper(allowObjectPassthrough: true);
        $entity = new UserEntity;

        $this->assertSame([$entity], $mapper->map(new Holder([$entity]), ArrayValueDto::class)->value);
        $this->assertSame($entity, $mapper->map(new Holder($entity), MixedValueDto::class)->value);
        $this->assertSame($entity, $mapper->map(new Holder($entity), ObjectValueDto::class)->value);
    }
}
