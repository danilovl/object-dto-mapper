<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Service;

use Closure;
use Danilovl\ObjectDtoMapper\Attribute\{
    MapTransformCallableInterface
};
use Danilovl\ObjectDtoMapper\Attribute\{
    Map,
    MapIfCallableInterface
};
use Danilovl\ObjectDtoMapper\Exception\{
    MappingIfException,
    MappingTransformException,
    NoSuchClassException,
    RuntimeException
};
use DateTimeInterface;
use Psr\Container\{
    ContainerExceptionInterface,
    ContainerInterface,
    NotFoundExceptionInterface
};
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\PropertyAccess\{
    PropertyAccess,
    PropertyAccessor
};
use Symfony\Component\Serializer\Attribute\Groups;

class ObjectToDtoMapper implements ObjectToDtoMapperInterface
{
    private PropertyAccessor $accessor;

    public function __construct(private readonly ?ContainerInterface $container = null)
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @template T of object
     * @param object $source
     * @param class-string<T> $target
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     * @return T
     * @throws NoSuchClassException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function map(object $source, string $target, array $allowGroups = [], array $ignoreGroups = []): object
    {
        if (!class_exists($target)) {
            $message = sprintf('Target class must exist: %s', $target);

            throw new NoSuchClassException($message);
        }

        $targetReflection = new ReflectionClass($target);
        $constructor = $targetReflection->getConstructor();

        if (!$constructor) {
            $message = sprintf('Target class must have a constructor: %s', $target);

            throw new RuntimeException($message);
        }

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $targetParameterName = $parameter->getName();
            $targetParameterType = $parameter->getType();

            $property = $targetReflection->getProperty($targetParameterName);
            $attributes = $property->getAttributes(Map::class);
            $sourceName = $targetParameterName;

            $if = null;
            $ifContainer = null;
            $transform = null;
            $transformContainer = null;

            if (count($attributes) > 0) {
                /** @var Map $attribute */
                $attribute = $attributes[0]->newInstance();
                if ($attribute->source !== null && $attribute->source !== '') {
                    $sourceName = $attribute->source;
                }

                $if = $attribute->if ?? null;
                $ifContainer = $attribute->ifContainer ?? null;
                $transform = $attribute->transform ?? null;
                $transformContainer = $attribute->transformContainer ?? null;
            }

            if (!$this->accessor->isReadable($source, $sourceName)) {
                $args[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;

                continue;
            }

            $sourceValue = $this->accessor->getValue($source, $sourceName);

            if ($if !== null || $ifContainer !== null) {
                $shouldMapIf = $this->shouldMapIf(
                    if: $if,
                    sourceValue: $sourceValue,
                    source: $source,
                    targetParameter: $targetParameterName,
                    target: $target
                );

                $shouldMapIfContainer = $this->shouldMapIfContainer(
                    ifContainer: $ifContainer,
                    entityValue: $sourceValue,
                    source: $source,
                    target: $target,
                    targetParameterName: $targetParameterName
                );

                if ($shouldMapIf === false || $shouldMapIfContainer === false) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $args[] = $parameter->getDefaultValue();
                    } elseif ($targetParameterType instanceof ReflectionNamedType && $targetParameterType->allowsNull()) {
                        $args[] = null;
                    } else {
                        $message = sprintf('Non-nullable property "%s" of "%s" skipped by Map(if) condition', $targetParameterName, $target);

                        throw new MappingIfException($message);
                    }

                    continue;
                }
            }

            $groupAttrs = $property->getAttributes(Groups::class);
            $propertyGroups = [];
            foreach ($groupAttrs as $attr) {
                $propertyGroups = array_merge($propertyGroups, $attr->newInstance()->getGroups());
            }

            if (!empty($ignoreGroups) && !empty(array_intersect($ignoreGroups, $propertyGroups))) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } elseif ($targetParameterType instanceof ReflectionNamedType && $targetParameterType->allowsNull()) {
                    $args[] = null;
                } else {
                    $message = sprintf('Non-nullable property "%s" of "%s" is restricted by ignoreGroups filter', $targetParameterName, $target);

                    throw new RuntimeException($message);
                }

                continue;
            }

            if (!empty($ignoreGroups) && !empty($allowGroups) && empty(array_intersect($allowGroups, $propertyGroups))) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } elseif ($targetParameterType instanceof ReflectionNamedType && $targetParameterType->allowsNull()) {
                    $args[] = null;
                } else {
                    $message = sprintf('Non-nullable property "%s" of "%s" is restricted by allowGroups filter', $targetParameterName, $target);

                    throw new RuntimeException($message);
                }

                continue;
            }

            if ($transform !== null) {
                $callable = $this->resolveCallableDefinition(
                    definition: $transform,
                    mapType: 'transform',
                    targetParameter: $targetParameterName
                );

                $sourceValue = $this->callCallable(
                    callable: $callable,
                    sourceValue: $sourceValue,
                    source: $source,
                    target: $target
                );
            } else if ($transformContainer !== null) {
                $callable = $this->resolveTransformContainerCallable($transformContainer);

                $sourceValue = $this->callCallable(
                    callable: $callable,
                    sourceValue: $sourceValue,
                    source: $source,
                    target: $target
                );
            } else {
                if ($sourceValue instanceof DateTimeInterface) {
                    $args[] = $sourceValue->format(DateTimeInterface::ATOM);

                    continue;
                }
            }

            if ($sourceValue === null) {
                if ($targetParameterType instanceof ReflectionNamedType && !$targetParameterType->allowsNull()) {
                    $message = sprintf('Non-nullable property "%s" of "%s" is null', $targetParameterName, $target);

                    throw new RuntimeException($message);
                }

                $args[] = null;

                continue;
            }

            if (is_iterable($sourceValue)) {
                $property = $targetReflection->getProperty($targetParameterName);
                $attributes = $property->getAttributes(Map::class);

                if (count($attributes) > 0) {
                    $attribute = $attributes[0]->newInstance();
                    /** @var class-string|null $targetClass */
                    $targetClass = $attribute->target;

                    if ($targetClass !== null) {
                        $args[] = $this->mapCollection(
                            collection: $sourceValue,
                            target: $targetClass,
                            allowGroups: $allowGroups,
                            ignoreGroups: $ignoreGroups
                        );
                    }

                    continue;
                }
            }

            if ($targetParameterType instanceof ReflectionNamedType && !$targetParameterType->isBuiltin()) {
                /** @var class-string $typeName */
                $typeName = $targetParameterType->getName();

                if (is_object($sourceValue)) {
                    $args[] = $this->map(
                        source: $sourceValue,
                        target: $typeName,
                        allowGroups: $allowGroups,
                        ignoreGroups: $ignoreGroups
                    );
                }

                continue;
            }

            $args[] = $sourceValue;
        }

        return $targetReflection->newInstanceArgs($args);
    }

    private function shouldMapIf(mixed $if, mixed $sourceValue, object $source, string $targetParameter, string $target): ?bool
    {
        if ($if === null) {
            return null;
        }

        if (is_bool($if)) {
            return $if;
        }

        $callableIf = $this->resolveCallableDefinition($if, 'if', $targetParameter);

        return (bool) $this->callCallable(
            callable: $callableIf,
            sourceValue: $sourceValue,
            source: $source,
            target: $target
        );
    }

    private function shouldMapIfContainer(?string $ifContainer, mixed $entityValue, object $source, string $target, string $targetParameterName): ?bool
    {
        if ($ifContainer === null) {
            return null;
        }

        $callableIf = $this->resolveIfContainerCallable($ifContainer);

        return (bool) $this->callCallable(
            callable: $callableIf,
            sourceValue: $entityValue,
            source: $source,
            target: $target
        );
    }

    private function resolveCallableDefinition(mixed $definition, string $mapType, string $targetParameter): callable
    {
        if (is_callable($definition)) {
            return $definition;
        }

        if (is_string($definition)) {
            if (!class_exists($definition)) {
                $message = sprintf('Map(%s) definition error: Class "%s" does not exist for property %s', $mapType, $definition, $targetParameter);

                throw new NoSuchClassException($message);
            }

            $class = new $definition;
            if (is_callable($class)) {
                return $class;
            }

            $message = sprintf('Map(%s) definition "%s" is not a valid callable for property %s', $mapType, $definition, $targetParameter);

            throw new MappingIfException($message);
        }

        $message = sprintf('Map(%s) definition is not callable', $mapType);

        throw new MappingIfException($message);
    }

    private function resolveIfContainerCallable(string $definition): callable
    {
        if ($this->container?->has($definition)) {
            $service = $this->container->get($definition);
        } else {
            $message = sprintf('Map(if) container service not found: "%s"', $definition);

            throw new MappingIfException($message);
        }

        if ($service instanceof MapIfCallableInterface) {
            return $service;
        }

        $message = sprintf('Map(if) container "%s" is not a valid callable service', $definition);

        throw new MappingIfException($message);
    }

    private function callCallable(callable $callable, mixed $sourceValue, object $source, string $target): mixed
    {
        if (is_array($callable)) {
            /** @var object|string $objOrClass */
            $objOrClass = $callable[0];
            /** @var string $method */
            $method = $callable[1];

            $reflection = new ReflectionMethod($objOrClass, $method);
        } elseif (is_string($callable)) {
            $reflection = new ReflectionFunction($callable);
        } elseif ($callable instanceof Closure) {
            $reflection = new ReflectionFunction($callable);
        } else {
            return $callable($sourceValue, $source, $target);
        }

        $paramCount = $reflection->isInternal() ? 1 : $reflection->getNumberOfParameters();
        $args = [$sourceValue, $source, $target];
        $args = array_slice($args, 0, $paramCount);

        return $callable(...$args);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveTransformContainerCallable(string $definition): callable
    {
        if ($definition === '') {
            $message = 'Map(transformContainer) definition must be a non-empty string';

            throw new MappingTransformException($message);
        }

        if ($this->container?->has($definition)) {
            $service = $this->container->get($definition);
        } else {
            $message = sprintf('Map(transformContainer) service not found: "%s"', $definition);

            throw new MappingTransformException($message);
        }

        if ($service instanceof MapTransformCallableInterface) {
            return $service;
        }

        $message = sprintf('Map(transformContainer) "%s" is not a valid callable service', $definition);

        throw new MappingTransformException($message);
    }

    /**
     * @template T of object
     * @param iterable $collection
     * @param class-string<T> $target
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     * @return array<T>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function mapCollection(iterable $collection, string $target, array $allowGroups = [], array $ignoreGroups = []): array
    {
        if (!class_exists($target)) {
            $message = sprintf('Target class must exist: %s', $target);

            throw new NoSuchClassException($message);
        }

        $result = [];
        foreach ($collection as $item) {
            if (is_object($item)) {
                $result[] = $this->map(
                    source: $item,
                    target: $target,
                    allowGroups: $allowGroups,
                    ignoreGroups: $ignoreGroups
                );
            }
        }

        return $result;
    }
}
