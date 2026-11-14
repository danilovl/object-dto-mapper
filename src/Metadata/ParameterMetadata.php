<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Metadata;

use Danilovl\ObjectDtoMapper\Service\ResolvedCallable;
use ReflectionParameter;
use ReflectionType;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

final readonly class ParameterMetadata
{
    /**
     * @param class-string|null $mapTarget
     * @param list<string> $builtinTypes Builtin types accepted by the parameter ("mixed" when untyped)
     * @param list<class-string> $classTypes Class/interface/enum types accepted by the parameter
     * @param string[] $groups
     */
    public function __construct(
        public string $name,
        public ReflectionParameter $reflection,
        public ?ReflectionType $type,
        public array $builtinTypes,
        public array $classTypes,
        public bool $allowsNull,
        public bool $hasDefault,
        public bool $isVariadic,
        public PropertyPathInterface $sourcePath,
        public ?string $mapTarget,
        public ?bool $ifConstant,
        public ?ResolvedCallable $ifCallable,
        public ?string $ifContainer,
        public ?ResolvedCallable $transformCallable,
        public ?string $transformContainer,
        public array $groups
    ) {}

    public function acceptsBuiltin(string $type): bool
    {
        return in_array($type, $this->builtinTypes, true);
    }

    public function isLoose(): bool
    {
        return $this->acceptsBuiltin('mixed');
    }

    /**
     * Read lazily: "new" initializers must create a fresh object for every DTO.
     */
    public function getDefaultValue(): mixed
    {
        return $this->reflection->getDefaultValue();
    }
}
