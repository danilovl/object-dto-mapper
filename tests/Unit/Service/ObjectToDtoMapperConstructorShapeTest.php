<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Exception\{
    NoSuchPropertyException,
    RuntimeException
};
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Conversion\{
    AddressEntity,
    AddressInterface,
    Status
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\{
    Holder,
    MockEntity
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Shape\{
    AbstractBaseDto,
    DefaultObjectDto,
    GetterDto,
    GetterEntity,
    InheritedConstructorDto,
    InvalidPathDto,
    NonPromotedParameterAttributeDto,
    NonPromotedPropertyAttributeDto,
    NonPromotedWithoutPropertyDto,
    NullableNameDto,
    NullWithDefaultDto,
    PrivateConstructorDto,
    ProfileEntity,
    PropertyPathDto,
    RequiredNameDto,
    TagsEntity,
    TypeMismatchDto,
    UninitializedEntity,
    UserWithProfileEntity,
    VariadicDto
};
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

class ObjectToDtoMapperConstructorShapeTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ObjectToDtoMapper;
    }

    public function testNonPromotedParameterWithAttributeOnParameter(): void
    {
        $result = $this->mapper->map(new MockEntity('John', 1), NonPromotedParameterAttributeDto::class);

        $this->assertSame('John', $result->title);
    }

    public function testNonPromotedParameterWithAttributeOnSameNamedProperty(): void
    {
        $result = $this->mapper->map(new MockEntity('John', 1), NonPromotedPropertyAttributeDto::class);

        $this->assertSame('John', $result->title);
    }

    public function testNonPromotedParameterWithoutProperty(): void
    {
        $result = $this->mapper->map(new MockEntity('John', 1), NonPromotedWithoutPropertyDto::class);

        $this->assertSame('JOHN', $result->upperName);
    }

    public function testInheritedConstructorWithPrivatePromotedProperty(): void
    {
        $result = $this->mapper->map(new MockEntity('John', 33), InheritedConstructorDto::class);

        $this->assertSame('John', $result->getName());
        $this->assertSame(33, $result->getYears());
    }

    public function testVariadicParameter(): void
    {
        $this->assertSame(['a', 'b'], $this->mapper->map(new TagsEntity('n', ['a', 'b']), VariadicDto::class)->tags);
        $this->assertSame(['single'], $this->mapper->map(new TagsEntity('n', 'single'), VariadicDto::class)->tags);
        $this->assertSame([], $this->mapper->map(new TagsEntity('n', null), VariadicDto::class)->tags);
        $this->assertSame(['a', 'b'], $this->mapper->map(new TagsEntity('n', ['x' => 'a', 'y' => 'b']), VariadicDto::class)->tags);
        $this->assertSame([], $this->mapper->map(new MockEntity('n', 1), VariadicDto::class)->tags);
    }

    public function testObjectDefaultValueIsFreshForEveryDto(): void
    {
        $first = $this->mapper->map(new MockEntity('A', 1), DefaultObjectDto::class);
        $second = $this->mapper->map(new MockEntity('B', 1), DefaultObjectDto::class);

        $this->assertTrue($first->options->enabled);
        $this->assertNotSame($first->options, $second->options);
    }

    /**
     * @param class-string $target
     */
    #[DataProvider('provideInvalidTargetClassCases')]
    public function testInvalidTargetClass(string $target, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->mapper->map(new MockEntity('A', 1), $target);
    }

    public static function provideInvalidTargetClassCases(): Generator
    {
        yield 'abstract class' => [AbstractBaseDto::class, 'must be a concrete instantiable class'];
        yield 'interface' => [AddressInterface::class, 'must be a concrete instantiable class'];
        yield 'enum' => [Status::class, 'must be a concrete instantiable class'];
        yield 'private constructor' => [PrivateConstructorDto::class, 'Target class constructor must be public'];
    }

    public function testPropertyPathSource(): void
    {
        $entity = new UserWithProfileEntity('John', new ProfileEntity(new AddressEntity('Prague', 'Wenceslas')), ['theme' => 'dark']);

        $result = $this->mapper->map($entity, PropertyPathDto::class);

        $this->assertEquals(new PropertyPathDto('Prague', 'Wenceslas', 'dark'), $result);
    }

    public function testPropertyPathWithNullInTheMiddleUsesDefaultOrNull(): void
    {
        $this->assertEquals(new PropertyPathDto(null, 'unknown', null), $this->mapper->map(new UserWithProfileEntity('John'), PropertyPathDto::class));
        $this->assertEquals(new PropertyPathDto(null, 'unknown', null), $this->mapper->map(new UserWithProfileEntity('John', new ProfileEntity), PropertyPathDto::class));
    }

    public function testInvalidPropertyPathThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Map(source) "name[" of property "name" in "%s" is not a valid property path', InvalidPathDto::class));

        $this->mapper->map(new MockEntity('A', 1), InvalidPathDto::class);
    }

    public function testGettersAreUsed(): void
    {
        $result = $this->mapper->map(new GetterEntity('John', true, false), GetterDto::class);

        $this->assertEquals(new GetterDto('John', true, false), $result);
    }

    public function testUninitializedPropertyIsTreatedAsNotReadable(): void
    {
        $this->assertNull($this->mapper->map(new UninitializedEntity, NullableNameDto::class)->name);
    }

    public function testNotReadableNonNullableWithoutDefaultThrows(): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->expectExceptionMessage(sprintf(
            'Non-nullable property "name" of "%s" has no default value and source path "name" cannot be read from "%s" (path: "name")',
            RequiredNameDto::class,
            Holder::class
        ));

        $this->mapper->map(new Holder, RequiredNameDto::class);
    }

    public function testNullSourceUsesDefaultForNonNullableAndNullForNullable(): void
    {
        $result = $this->mapper->map(new Holder, NullWithDefaultDto::class);
        $this->assertSame('default@example.com', $result->email);
        $this->assertSame('nullable default', $result->name);

        $entity = new MockEntity('ignored', 1, null);
        $result = $this->mapper->map($entity, NullWithDefaultDto::class);
        $this->assertSame('default@example.com', $result->email);
    }

    public function testExplicitNullIsKeptForNullableWithDefault(): void
    {
        $entity = new class() {
            public ?string $name = null;
        };

        $result = $this->mapper->map($entity, NullWithDefaultDto::class);

        $this->assertNull($result->name);
    }

    public function testTypeErrorIsWrapped(): void
    {
        try {
            $this->mapper->map(new MockEntity('John', 1), TypeMismatchDto::class);
            $this->fail('Exception expected');
        } catch (RuntimeException $exception) {
            $this->assertStringStartsWith(sprintf('Cannot create "%s"', TypeMismatchDto::class), $exception->getMessage());
            $this->assertInstanceOf(TypeError::class, $exception->getPrevious());
        }
    }
}
