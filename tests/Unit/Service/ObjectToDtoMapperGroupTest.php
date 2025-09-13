<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\DTO\MockDtoWithGroups;
use Danilovl\ObjectDtoMapper\Tests\Mock\Entity\MockEntity;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ObjectToDtoMapperGroupTest extends TestCase
{
    private ObjectToDtoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ObjectToDtoMapper;
    }

    #[DataProvider('provideGroupsFilteringCases')]
    public function testGroupsFiltering(array $allowGroups, array $ignoreGroups, ?object $expectedDto, ?array $expectedException): void
    {
        $entity = new MockEntity('John Doe', 30, 'john@example.com');

        if ($expectedException !== null) {
            [$message] = $expectedException;
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);
        }

        $result = $this->mapper->map(
            $entity,
            MockDtoWithGroups::class,
            $allowGroups,
            $ignoreGroups
        );

        if ($expectedException === null) {
            $this->assertEquals($expectedDto, $result);
        }
    }

    public static function provideGroupsFilteringCases(): Generator
    {
        $msgIgnoreAge = sprintf('Non-nullable property "%s" of "%s" is restricted by ignoreGroups filter', 'age', MockDtoWithGroups::class);
        yield 'ignoreGroups only (admin) -> exception because age non-nullable' => [
            [],
            ['admin'],
            null,
            [$msgIgnoreAge]
        ];

        yield 'ignoreGroups only (private) -> email null' => [
            [],
            ['private'],
            new MockDtoWithGroups('John Doe', 30, null),
            null
        ];

        yield 'allowGroups only (public) -> no filtering effect' => [
            ['public'],
            [],
            new MockDtoWithGroups('John Doe', 30, 'john@example.com'),
            null
        ];

        $msgAllowAge = sprintf('Non-nullable property "%s" of "%s" is restricted by allowGroups filter', 'age', MockDtoWithGroups::class);
        yield 'allowGroups(public) + ignoreGroups(dummy) -> age excluded -> exception' => [
            ['public'],
            ['dummy'],
            null,
            [$msgAllowAge]
        ];

        yield 'allowGroups(public) + ignoreGroups(private) -> exception on age first' => [
            ['public'],
            ['private'],
            null,
            [$msgAllowAge]
        ];

        yield 'allowGroups(public,admin) + ignoreGroups(private) -> OK with email null' => [
            ['public', 'admin'],
            ['private'],
            new MockDtoWithGroups('John Doe', 30, null),
            null
        ];
    }
}
