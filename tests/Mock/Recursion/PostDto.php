<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Recursion;

final class PostDto
{
    public function __construct(
        public string $title,
        public AuthorSummaryDto $author
    ) {}
}
