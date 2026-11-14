<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\Service;

use Danilovl\ObjectDtoMapper\Exception\{
    CircularReferenceException,
    MaxDepthException,
    RuntimeException
};
use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Danilovl\ObjectDtoMapper\Tests\Mock\Recursion\{
    AuthorEntity,
    AuthorSummaryDto,
    AuthorWithPostsDto,
    CategoryDto,
    CategoryEntity,
    PostDto,
    PostEntity
};
use PHPUnit\Framework\TestCase;

class ObjectToDtoMapperRecursionTest extends TestCase
{
    public function testCircularReferenceIsDetected(): void
    {
        $author = new AuthorEntity('Jane');
        $author->posts = [new PostEntity('First', $author)];

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage(sprintf(
            'Circular reference detected: object of class "%s" is already being mapped to "%s" (path: "posts[0].author")',
            AuthorEntity::class,
            AuthorWithPostsDto::class
        ));

        new ObjectToDtoMapper()->map($author, AuthorWithPostsDto::class);
    }

    public function testSelfReferenceIsDetected(): void
    {
        $category = new CategoryEntity('Loop');
        $category->parent = $category;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('(path: "parent")');

        new ObjectToDtoMapper()->map($category, CategoryDto::class);
    }

    public function testSameObjectInSeveralPlacesIsNotACycle(): void
    {
        $author = new AuthorEntity('Jane');
        $posts = [new PostEntity('First', $author), new PostEntity('Second', $author)];

        $result = new ObjectToDtoMapper()->mapCollection($posts, PostDto::class);

        $this->assertEquals([
            new PostDto('First', new AuthorSummaryDto('Jane')),
            new PostDto('Second', new AuthorSummaryDto('Jane'))
        ], $result);
    }

    public function testDeepTreeWithinMaxDepth(): void
    {
        $category = $this->createChain(5);

        $result = new ObjectToDtoMapper(maxDepth: 5)->map($category, CategoryDto::class);

        $this->assertSame('level-5', $result->name);
        $this->assertSame('level-1', $result->parent?->parent?->parent?->parent?->name);
        $this->assertNull($result->parent->parent->parent->parent->parent);
    }

    public function testMaxDepthExceeded(): void
    {
        $category = $this->createChain(5);

        $this->expectException(MaxDepthException::class);
        $this->expectExceptionMessage('Maximum mapping depth of 3 exceeded');

        new ObjectToDtoMapper(maxDepth: 3)->map($category, CategoryDto::class);
    }

    public function testMaxDepthMustBePositive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Max depth must be greater than 0, 0 given');

        new ObjectToDtoMapper(maxDepth: 0);
    }

    public function testMapperIsReusableAfterFailure(): void
    {
        $mapper = new ObjectToDtoMapper;
        $loop = new CategoryEntity('Loop');
        $loop->parent = $loop;

        try {
            $mapper->map($loop, CategoryDto::class);
            $this->fail('Exception expected');
        } catch (CircularReferenceException) {
        }

        $result = $mapper->map(new CategoryEntity('Ok'), CategoryDto::class);
        $this->assertEquals(new CategoryDto('Ok'), $result);
    }

    private function createChain(int $levels): CategoryEntity
    {
        $category = null;
        for ($i = 1; $i <= $levels; $i++) {
            $category = new CategoryEntity('level-' . $i, $category);
        }

        /** @var CategoryEntity $category */
        return $category;
    }
}
