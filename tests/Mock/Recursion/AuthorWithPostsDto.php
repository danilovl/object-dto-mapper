<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Recursion;

use Danilovl\ObjectDtoMapper\Attribute\Map;

final class AuthorWithPostsDto
{
    /**
     * @param PostWithAuthorDto[] $posts
     */
    public function __construct(
        public string $name,
        #[Map(target: PostWithAuthorDto::class)]
        public array $posts
    ) {}
}
