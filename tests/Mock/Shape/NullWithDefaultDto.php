<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class NullWithDefaultDto
{
    public function __construct(
        public string $email = 'default@example.com',
        public ?string $name = 'nullable default'
    ) {}
}
