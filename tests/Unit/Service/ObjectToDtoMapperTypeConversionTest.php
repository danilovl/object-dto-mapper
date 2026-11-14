<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Exception\RuntimeException;
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Conversion\{
    AddressDto,
    AddressEntity,
    CoercionDto,
    DateTimeDto,
    DateTimeEntity,
    EnumEntity,
    EnumToScalarDto,
    ImplementingAddressDto,
    InterfaceTypedDto,
    InterfaceWithTargetDto,
    InvalidIntEnumDto,
    InvalidStringEnumDto,
    NullableNestedDto,
    Priority,
    ScalarToEnumDto,
    Status,
    Suit,
    UnionNestedDto
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\{
    Holder,
    MockEntity
};
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class ObjectToDtoMapperTypeConversionTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ObjectToDtoMapper;
    }

    public function testDateTimeConversions(): void
    {
        $immutable = new DateTimeImmutable('2020-01-02T03:04:05+02:00');
        $mutable = new DateTime('2021-06-07T08:09:10+00:00', new DateTimeZone('UTC'));

        $result = $this->mapper->map(new DateTimeEntity($immutable, $mutable), DateTimeDto::class);

        $this->assertSame('2020-01-02T03:04:05+02:00', $result->asString);
        $this->assertSame('2020-01-02T03:04:05+02:00', $result->asNullableString);
        $this->assertSame($immutable, $result->asInterface);

        $this->assertInstanceOf(DateTimeImmutable::class, $result->toImmutable);
        $this->assertSame($mutable->format(DATE_ATOM), $result->toImmutable->format(DATE_ATOM));

        $this->assertInstanceOf(DateTime::class, $result->toMutable);
        $this->assertSame($immutable->format(DATE_ATOM), $result->toMutable->format(DATE_ATOM));

        $this->assertSame('2020-01-02T03:04:05+02:00', $result->asMixed);
        $this->assertSame('2020-01-02T03:04:05+02:00', $result->untyped);
        $this->assertSame($mutable, $result->asObject);
    }

    public function testEnumToScalar(): void
    {
        $result = $this->mapper->map(new EnumEntity, EnumToScalarDto::class);

        $this->assertSame('active', $result->statusValue);
        $this->assertSame(2, $result->priorityValue);
        $this->assertSame('2', $result->priorityAsString);
        $this->assertSame('Hearts', $result->suitName);
        $this->assertSame(Status::Active, $result->sameEnum);
        $this->assertSame(Status::Active, $result->mixedEnum);
        $this->assertSame(Suit::Hearts, $result->sameUnitEnum);
    }

    public function testScalarToEnum(): void
    {
        $result = $this->mapper->map(new EnumEntity, ScalarToEnumDto::class);

        $this->assertSame(Status::Blocked, $result->status);
        $this->assertSame(Priority::Low, $result->priority);
        $this->assertSame(Priority::High, $result->priorityFromString);
        $this->assertSame('blocked', $result->statusOrString);
        $this->assertNull($result->nullable);
    }

    public function testInvalidStringEnumValueThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Value \'unknown\' is not a valid backing value for enum "%s" of property "status" in "%s" (path: "status")',
            Status::class,
            InvalidStringEnumDto::class
        ));

        $this->mapper->map(new EnumEntity, InvalidStringEnumDto::class);
    }

    public function testNonNumericStringForIntEnumThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('is not a valid backing value for enum "%s"', Priority::class));

        $this->mapper->map(new EnumEntity, InvalidIntEnumDto::class);
    }

    public function testScalarCoercion(): void
    {
        $result = $this->mapper->map(new MockEntity('John', 42), CoercionDto::class);

        $this->assertSame('42', $result->age);
    }

    public function testUnionTypeNestedMapping(): void
    {
        $result = $this->mapper->map(new Holder(new AddressEntity('Prague')), UnionNestedDto::class);

        $this->assertEquals(new AddressDto('Prague'), $result->address);
    }

    public function testUnionTypeScalarIsKept(): void
    {
        $result = $this->mapper->map(new Holder(5), UnionNestedDto::class);

        $this->assertSame(5, $result->address);
    }

    public function testNullableNestedObject(): void
    {
        $this->assertNull($this->mapper->map(new Holder(null), NullableNestedDto::class)->address);

        $result = $this->mapper->map(new Holder(new AddressEntity('Brno')), NullableNestedDto::class);
        $this->assertEquals(new AddressDto('Brno'), $result->address);
    }

    public function testInterfaceTypeWithoutTargetThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Cannot map object of class "%s" to property "address" of "%s"', AddressEntity::class, InterfaceTypedDto::class));

        $this->mapper->map(new Holder(new AddressEntity('Prague')), InterfaceTypedDto::class);
    }

    public function testInterfaceTypeAcceptsImplementingObjectAsIs(): void
    {
        $address = new ImplementingAddressDto('Prague');

        $result = $this->mapper->map(new Holder($address), InterfaceTypedDto::class);

        $this->assertSame($address, $result->address);
    }

    public function testInterfaceTypeWithMapTarget(): void
    {
        $result = $this->mapper->map(new Holder(new AddressEntity('Prague')), InterfaceWithTargetDto::class);

        $this->assertEquals(new ImplementingAddressDto('Prague'), $result->address);
    }
}
