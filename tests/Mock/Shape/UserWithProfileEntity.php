<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Shape;

final class UserWithProfileEntity
{
    /**
     * @param array<string, string> $settings
     */
    public function __construct(
        public string $name,
        public ?ProfileEntity $profile = null,
        public array $settings = []
    ) {}
}
