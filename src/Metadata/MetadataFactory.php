<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Metadata;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use Danilovl\ObjectDtoMapper\Exception\{
    NoSuchClassException,
    RuntimeException
};
use Danilovl\ObjectDtoMapper\Service\CallableResolver;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Serializer\Attribute\Groups;

final class MetadataFactory
{
    /**
     * @var array<class-string, ClassMetadata<object>>
     */
    private array $cache = [];

    public function __construct(private readonly CallableResolver $callableResolver) {}

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassMetadata<T>
     */
    public function getMetadata(string $class): ClassMetadata
    {
        if (isset($this->cache[$class])) {
            /** @var ClassMetadata<T> */
            return $this->cache[$class];
        }

        if (!class_exists($class) && !interface_exists($class)) {
            throw new NoSuchClassException(sprintf('Target class must exist: %s', $class));
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isInterface() || $reflection->isAbstract() || $reflection->isEnum()) {
            $message = sprintf('Target class must be a concrete instantiable class, "%s" is an interface, abstract class or enum', $class);

            throw new RuntimeException($message);
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            throw new RuntimeException(sprintf('Target class must have a constructor: %s', $class));
        }

        if (!$constructor->isPublic()) {
            throw new RuntimeException(sprintf('Target class constructor must be public: %s', $class));
        }

        $parameters = [];
        foreach ($constructor->getParameters() as $parameter) {
            $parameters[] = $this->createParameterMetadata($parameter, $class);
        }

        /** @var ClassMetadata<T> $metadata */
        $metadata = new ClassMetadata($reflection->getName(), $reflection, $parameters);

        return $this->cache[$class] = $metadata;
    }

    private function createParameterMetadata(ReflectionParameter $parameter, string $class): ParameterMetadata
    {
        $name = $parameter->getName();
        $property = $this->findProperty($parameter);
        $map = $this->readMapAttribute($parameter, $property);

        $sourcePath = $map?->source !== null && $map->source !== '' ? $map->source : $name;

        try {
            $propertyPath = new PropertyPath($sourcePath);
        } catch (InvalidPropertyPathException $exception) {
            $message = sprintf('Map(source) "%s" of property "%s" in "%s" is not a valid property path: %s', $sourcePath, $name, $class, $exception->getMessage());

            throw new RuntimeException($message, 0, $exception);
        }

        $mapTarget = $map?->target;
        if ($mapTarget !== null && !class_exists($mapTarget)) {
            throw new NoSuchClassException(sprintf('Target class must exist: %s', $mapTarget));
        }

        $ifConstant = null;
        $ifCallable = null;
        if (is_bool($map?->if)) {
            $ifConstant = $map->if;
        } elseif ($map?->if !== null) {
            $ifCallable = $this->callableResolver->resolve($map->if, CallableResolver::TYPE_IF, $name);
        }

        $transformCallable = null;
        if ($map?->transform !== null) {
            $transformCallable = $this->callableResolver->resolve($map->transform, CallableResolver::TYPE_TRANSFORM, $name);
        }

        $type = $parameter->getType();
        [$builtinTypes, $classTypes] = $this->splitTypes($type, $parameter);

        return new ParameterMetadata(
            name: $name,
            reflection: $parameter,
            type: $type,
            builtinTypes: $builtinTypes,
            classTypes: $classTypes,
            allowsNull: $type === null || $type->allowsNull(),
            hasDefault: $parameter->isDefaultValueAvailable(),
            isVariadic: $parameter->isVariadic(),
            sourcePath: $propertyPath,
            mapTarget: $mapTarget,
            ifConstant: $ifConstant,
            ifCallable: $ifCallable,
            ifContainer: $map?->ifContainer,
            transformCallable: $transformCallable,
            transformContainer: $map?->transformContainer,
            groups: $this->readGroups($property)
        );
    }

    private function findProperty(ReflectionParameter $parameter): ?ReflectionProperty
    {
        $declaringClass = $parameter->getDeclaringClass();
        if ($declaringClass === null || !$declaringClass->hasProperty($parameter->getName())) {
            return null;
        }

        return $declaringClass->getProperty($parameter->getName());
    }

    private function readMapAttribute(ReflectionParameter $parameter, ?ReflectionProperty $property): ?Map
    {
        $attributes = $parameter->isPromoted() ? [] : $parameter->getAttributes(Map::class);
        if (count($attributes) === 0 && $property !== null) {
            $attributes = $property->getAttributes(Map::class);
        }

        return count($attributes) > 0 ? $attributes[0]->newInstance() : null;
    }

    /**
     * @return string[]
     */
    private function readGroups(?ReflectionProperty $property): array
    {
        if ($property === null || !class_exists(Groups::class)) {
            return [];
        }

        $groups = [];
        foreach ($property->getAttributes(Groups::class) as $attribute) {
            $groups = array_merge($groups, $attribute->newInstance()->groups);
        }

        return array_values(array_unique($groups));
    }

    /**
     * @return array{list<string>, list<class-string>}
     */
    private function splitTypes(?ReflectionType $type, ReflectionParameter $parameter): array
    {
        if ($type === null) {
            return [['mixed'], []];
        }

        $topLevel = match (true) {
            $type instanceof ReflectionUnionType, $type instanceof ReflectionIntersectionType => $type->getTypes(),
            default => [$type]
        };

        $members = [];
        foreach ($topLevel as $member) {
            if ($member instanceof ReflectionIntersectionType) {
                array_push($members, ...$member->getTypes());
            } else {
                $members[] = $member;
            }
        }

        $builtinTypes = [];
        $classTypes = [];

        foreach ($members as $member) {
            if (!$member instanceof ReflectionNamedType) {
                continue;
            }

            $name = $member->getName();
            if ($member->isBuiltin()) {
                $builtinTypes[] = $name;

                continue;
            }

            if ($name === 'self' || $name === 'static') {
                $name = $parameter->getDeclaringClass()?->getName() ?? $name;
            } elseif ($name === 'parent') {
                $name = $parameter->getDeclaringClass()?->getParentClass() ?: null;
                $name = $name?->getName() ?? 'parent';
            }

            /** @var class-string $name */
            $classTypes[] = $name;
        }

        return [$builtinTypes, $classTypes];
    }
}
