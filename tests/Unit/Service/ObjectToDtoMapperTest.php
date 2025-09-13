<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\DTO\{
    MockDto,
    MockDtoForNonReadableTest,
    MockDtoWithCollection,
    MockDtoWithDefaultValue,
    MockNestedDto
};
use Danilovl\ObjectDtoMapper\Tests\Mock\DTO\MockDtoWithDateTimeString;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\{
    MockEntity,
    MockEntityWithAltNames,
    MockEntityWithCollection,
    MockEntityWithNonReadableProperty,
    MockNestedEntity
};
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\MockEntityWithDateTime;
use Danilovl\ObjectDtoMapper\Tests\Mock\MapSource\MockDtoWithSource;
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ObjectToDtoMapperTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ObjectToDtoMapper;
    }

    public function testDateTimeIsFormattedToAtom(): void
    {
        $entity = new MockEntityWithDateTime('Event', new DateTimeImmutable('2020-01-02T03:04:05+00:00'));

        $result = $this->mapper->map($entity, MockDtoWithDateTimeString::class);

        $this->assertSame('Event', $result->title);
        $this->assertSame('2020-01-02T03:04:05+00:00', $result->happenedAt);
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @param T $expectedDto
     */
    #[DataProvider('provideBasicMappingCases')]
    public function testBasicMapping(object $entity, string $dtoClass, object $expectedDto): void
    {
        $result = $this->mapper->map($entity, $dtoClass);

        $this->assertEquals($expectedDto, $result);
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @param T $expectedDto
     */
    #[DataProvider('provideCollectionMappingCases')]
    public function testCollectionMapping(object $entity, string $dtoClass, object $expectedDto): void
    {
        $result = $this->mapper->map($entity, $dtoClass);

        $this->assertEquals($expectedDto, $result);
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @param T $expectedDto
     */
    #[DataProvider('provideNestedObjectMappingCases')]
    public function testNestedObjectMapping(object $entity, string $dtoClass, object $expectedDto): void
    {
        $result = $this->mapper->map($entity, $dtoClass);

        $this->assertEquals($expectedDto, $result);
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @param T $expectedDto
     */
    #[DataProvider('provideNonReadablePropertyCases')]
    public function testNonReadableProperty(object $entity, string $dtoClass, object $expectedDto): void
    {
        $result = $this->mapper->map($entity, $dtoClass);

        $this->assertEquals($expectedDto, $result);
    }

    /**
     * @return Generator<int, array{object, class-string<object>, object}>
     */
    public static function provideBasicMappingCases(): Generator
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');
        $expectedDto = new MockDto('John Doe', 30, 'john@example.com');

        yield [
            $entity,
            MockDto::class,
            $expectedDto
        ];

        $entity = new MockEntity('Jane Doe', 25, null);
        $expectedDto = new MockDto('Jane Doe', 25, null);

        yield [
            $entity,
            MockDto::class,
            $expectedDto
        ];

        $altEntity = new MockEntityWithAltNames('Hello', 7, null);
        $expectedWithSource = new MockDtoWithSource('Hello', 7, null);
        yield [
            $altEntity,
            MockDtoWithSource::class,
            $expectedWithSource
        ];
    }

    /**
     * @return Generator<int, array{object, class-string<object>, object}>
     */
    public static function provideCollectionMappingCases(): Generator
    {
        $items = [
            new MockEntity('Item 1', 1, 'item1@example.com'),
            new MockEntity('Item 2', 2, 'item2@example.com')
        ];

        $entity = new MockEntityWithCollection(
            'Collection Title',
            $items,
            'Collection Description'
        );

        $expectedItems = [
            new MockDto('Item 1', 1, 'item1@example.com'),
            new MockDto('Item 2', 2, 'item2@example.com')
        ];

        $expectedDto = new MockDtoWithCollection(
            'Collection Title',
            $expectedItems,
            'Collection Description'
        );

        yield [
            $entity,
            MockDtoWithCollection::class,
            $expectedDto
        ];
    }

    /**
     * @return Generator<int, array{object, class-string<object>, object}>
     */
    public static function provideNestedObjectMappingCases(): Generator
    {
        $nestedEntity = new MockEntity('Nested', 5, 'nested@example.com');
        $entity = new MockNestedEntity('Parent', $nestedEntity);

        $expectedNestedDto = new MockDto('Nested', 5, 'nested@example.com');
        $expectedDto = new MockNestedDto('Parent', $expectedNestedDto);

        yield [
            $entity,
            MockNestedDto::class,
            $expectedDto
        ];
    }

    /**
     * @return Generator<int, array{object, class-string<object>, object}>
     */
    public static function provideNonReadablePropertyCases(): Generator
    {
        $entity = new MockEntityWithNonReadableProperty('John Doe', 30, 'john@example.com');
        $expectedDto = new MockDtoForNonReadableTest('John Doe', 30, null);

        yield [
            $entity,
            MockDtoForNonReadableTest::class,
            $expectedDto
        ];

        $entity = new MockEntityWithNonReadableProperty('Jane Doe', 25, 'jane@example.com');
        $expectedDto = new MockDtoWithDefaultValue('Jane Doe', 25, 'default@example.com');

        yield [
            $entity,
            MockDtoWithDefaultValue::class,
            $expectedDto
        ];
    }
}
