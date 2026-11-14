<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Exception\RuntimeException;
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\DTO\MockDtoWithGroups;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\MockEntity;
use Danilovl\ObjectDtoMapper\Tests\Mock\Group\{
    ChildDto,
    ChildEntity,
    GroupedIfDto,
    MultiGroupDto,
    ParentDto,
    ParentEntity,
    SpyEntity,
    WithoutGroupDto
};
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ObjectToDtoMapperGroupTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ObjectToDtoMapper;
    }

    /**
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     */
    #[DataProvider('provideGroupsFilteringCases')]
    public function testGroupsFiltering(array $allowGroups, array $ignoreGroups, ?object $expectedDto, ?string $expectedExceptionMessage): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');

        if ($expectedExceptionMessage !== null) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $result = $this->mapper->map($entity, MockDtoWithGroups::class, $allowGroups, $ignoreGroups);

        $this->assertEquals($expectedDto, $result);
    }

    public static function provideGroupsFilteringCases(): Generator
    {
        $msgIgnoreAge = sprintf('Non-nullable property "%s" of "%s" is restricted by ignoreGroups filter', 'age', MockDtoWithGroups::class);
        $msgAllowAge = sprintf('Non-nullable property "%s" of "%s" is restricted by allowGroups filter', 'age', MockDtoWithGroups::class);

        yield 'no groups -> everything mapped' => [
            [],
            [],
            new MockDtoWithGroups('John Doe', 30, 'john@example.com'),
            null
        ];

        yield 'ignoreGroups only (admin) -> exception because age non-nullable' => [
            [],
            ['admin'],
            null,
            $msgIgnoreAge
        ];

        yield 'ignoreGroups only (private) -> email null' => [
            [],
            ['private'],
            new MockDtoWithGroups('John Doe', 30, null),
            null
        ];

        yield 'allowGroups only (public) -> age excluded -> exception' => [
            ['public'],
            [],
            null,
            $msgAllowAge
        ];

        yield 'allowGroups only (public,admin) -> email excluded' => [
            ['public', 'admin'],
            [],
            new MockDtoWithGroups('John Doe', 30, null),
            null
        ];

        yield 'allowGroups all groups -> everything mapped' => [
            ['public', 'admin', 'private'],
            [],
            new MockDtoWithGroups('John Doe', 30, 'john@example.com'),
            null
        ];

        yield 'allowGroups(public) + ignoreGroups(dummy) -> age excluded -> exception' => [
            ['public'],
            ['dummy'],
            null,
            $msgAllowAge
        ];

        yield 'allowGroups(public) + ignoreGroups(private) -> exception on age' => [
            ['public'],
            ['private'],
            null,
            $msgAllowAge
        ];

        yield 'allowGroups(public,admin) + ignoreGroups(private) -> OK with email null' => [
            ['public', 'admin'],
            ['private'],
            new MockDtoWithGroups('John Doe', 30, null),
            null
        ];

        yield 'ignoreGroups wins over allowGroups' => [
            ['public', 'admin', 'private'],
            ['admin'],
            null,
            $msgIgnoreAge
        ];

        yield 'unknown allow group -> name excluded first' => [
            ['unknown'],
            [],
            null,
            sprintf('Non-nullable property "%s" of "%s" is restricted by allowGroups filter', 'name', MockDtoWithGroups::class)
        ];
    }

    public function testPropertyWithoutGroupsIsExcludedWhenAllowGroupsSet(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');

        $result = $this->mapper->map($entity, WithoutGroupDto::class, allowGroups: ['public']);

        $this->assertEquals(new WithoutGroupDto('John Doe', null), $result);
    }

    public function testPropertyWithoutGroupsIsMappedWhenOnlyIgnoreGroupsSet(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');

        $result = $this->mapper->map($entity, WithoutGroupDto::class, ignoreGroups: ['admin']);

        $this->assertEquals(new WithoutGroupDto('John Doe', 30), $result);
    }

    public function testPropertyInMultipleGroups(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');

        $this->assertEquals(new MultiGroupDto('John Doe', 30), $this->mapper->map($entity, MultiGroupDto::class, allowGroups: ['list']));
        $this->assertEquals(new MultiGroupDto('John Doe', null), $this->mapper->map($entity, MultiGroupDto::class, allowGroups: ['public']));
        $this->assertEquals(new MultiGroupDto('John Doe', null), $this->mapper->map($entity, MultiGroupDto::class, allowGroups: ['list'], ignoreGroups: ['admin']));
    }

    public function testGroupsArePropagatedToNestedObjects(): void
    {
        $entity = new ParentEntity('Parent', new ChildEntity('Prague', 'top-secret'));

        $public = $this->mapper->map($entity, ParentDto::class, allowGroups: ['public']);
        $this->assertEquals(new ParentDto('Parent', new ChildDto('Prague', null)), $public);

        $admin = $this->mapper->map($entity, ParentDto::class, allowGroups: ['public', 'admin']);
        $this->assertEquals(new ParentDto('Parent', new ChildDto('Prague', 'top-secret')), $admin);

        $ignored = $this->mapper->map($entity, ParentDto::class, ignoreGroups: ['admin']);
        $this->assertEquals(new ParentDto('Parent', new ChildDto('Prague', null)), $ignored);
    }

    public function testExcludedPropertyGetterIsNeverCalled(): void
    {
        $entity = new SpyEntity;

        $result = $this->mapper->map($entity, MockDtoWithGroups::class, ignoreGroups: ['private']);

        $this->assertNull($result->email);
        $this->assertArrayNotHasKey('email', $entity->calls);
    }

    public function testMappedPropertyGetterIsCalledExactlyOnce(): void
    {
        $entity = new SpyEntity;

        $this->mapper->map($entity, MockDtoWithGroups::class);

        $this->assertSame(['name' => 1, 'age' => 1, 'email' => 1], $entity->calls);
    }

    public function testIfConditionIsNotEvaluatedForExcludedProperty(): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');

        $result = $this->mapper->map($entity, GroupedIfDto::class, ignoreGroups: ['admin']);

        $this->assertEquals(new GroupedIfDto('John Doe', null), $result);
    }
}
