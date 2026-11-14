<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use ArrayIterator;
use Danilovl\ObjectDtoMapper\Exception\{
    NoSuchClassException,
    RuntimeException
};
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Collection\{
    IterableListDto,
    ItemDto,
    ItemEntity,
    ListDto,
    MissingTargetDto,
    NullableListDto,
    ScalarArrayDto,
    ScalarIterableDto
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\Holder;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ObjectToDtoMapperCollectionTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ObjectToDtoMapper;
    }

    /**
     * @param array<mixed> $expected
     */
    #[DataProvider('provideCollectionSourcesCases')]
    public function testCollectionSources(mixed $source, array $expected): void
    {
        $result = $this->mapper->map(new Holder($source), ListDto::class);

        $this->assertEquals($expected, $result->value);
    }

    public static function provideCollectionSourcesCases(): Generator
    {
        $a = new ItemEntity('A');
        $b = new ItemEntity('B');

        yield 'list array' => [[$a, $b], [new ItemDto('A'), new ItemDto('B')]];
        yield 'empty array' => [[], []];
        yield 'traversable' => [new ArrayIterator([$a, $b]), [new ItemDto('A'), new ItemDto('B')]];
        yield 'generator' => [(static function () use ($a, $b): Generator {
            yield $a;
            yield $b;
        })(), [new ItemDto('A'), new ItemDto('B')]];
        yield 'integer keys with gaps are re-indexed' => [[3 => $a, 7 => $b], [new ItemDto('A'), new ItemDto('B')]];
        yield 'string keys are preserved' => [['first' => $a, 'second' => $b], ['first' => new ItemDto('A'), 'second' => new ItemDto('B')]];
        yield 'null items are kept' => [[$a, null, $b], [new ItemDto('A'), null, new ItemDto('B')]];
        yield 'items already of target type are kept' => [[new ItemDto('X'), $a], [new ItemDto('X'), new ItemDto('A')]];
        yield 'generator with object keys falls back to a list' => [(static function () use ($a): Generator {
            yield $a => $a;
        })(), [new ItemDto('A')]];
    }

    public function testScalarCollectionItemThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Collection item must be an object to be mapped to "%s", string given (path: "value[1]")', ItemDto::class));

        $this->mapper->map(new Holder([new ItemEntity('A'), 'scalar']), ListDto::class);
    }

    public function testNullableCollection(): void
    {
        $this->assertNull($this->mapper->map(new Holder(null), NullableListDto::class)->value);
        $this->assertEquals([new ItemDto('A')], $this->mapper->map(new Holder([new ItemEntity('A')]), NullableListDto::class)->value);
    }

    public function testIterableTypedCollection(): void
    {
        $result = $this->mapper->map(new Holder(new ArrayIterator([new ItemEntity('A')])), IterableListDto::class);

        $this->assertEquals([new ItemDto('A')], $result->value);
    }

    public function testMapTargetClassMustExist(): void
    {
        $this->expectException(NoSuchClassException::class);
        $this->expectExceptionMessage('Target class must exist: Danilovl\ObjectDtoMapper\Tests\Missing\ItemDto');

        $this->mapper->map(new Holder([]), MissingTargetDto::class);
    }

    public function testScalarArrayIsPassedAsIs(): void
    {
        $value = ['a' => 1, 'b' => ['nested' => true], 'c' => null];

        $result = $this->mapper->map(new Holder($value), ScalarArrayDto::class);

        $this->assertSame($value, $result->value);
    }

    public function testScalarTraversableIsConvertedToArray(): void
    {
        $result = $this->mapper->map(new Holder(new ArrayIterator([1, 2, 3])), ScalarArrayDto::class);
        $this->assertSame([1, 2, 3], $result->value);

        $iterable = $this->mapper->map(new Holder(new ArrayIterator(['x' => 1])), ScalarIterableDto::class);
        $this->assertSame(['x' => 1], $iterable->value);
    }

    public function testPublicMapCollection(): void
    {
        $result = $this->mapper->mapCollection([new ItemEntity('A'), new ItemEntity('B')], ItemDto::class);

        $this->assertEquals([new ItemDto('A'), new ItemDto('B')], $result);
    }

    public function testPublicMapCollectionWithTraversableAndKeys(): void
    {
        $result = $this->mapper->mapCollection(new ArrayIterator(['a' => new ItemEntity('A')]), ItemDto::class);

        $this->assertEquals(['a' => new ItemDto('A')], $result);
    }

    public function testPublicMapCollectionEmptyStillValidatesTarget(): void
    {
        $this->expectException(NoSuchClassException::class);

        /** @phpstan-ignore argument.type */
        $this->mapper->mapCollection([], 'Danilovl\Missing\Dto');
    }

    public function testPublicMapCollectionScalarItemThrowsWithPath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('(path: "[0]")');

        /** @phpstan-ignore argument.type */
        $this->mapper->mapCollection([1], ItemDto::class);
    }
}
