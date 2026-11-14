<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Recursion;

final class AuthorEntity
{
    /**
     * @var PostEntity[]
     */
    public array $posts = [];

    public function __construct(public string $name) {}
}
