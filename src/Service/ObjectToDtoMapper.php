<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Service;

use BackedEnum;
use Danilovl\ObjectDtoMapper\Exception\{
    MappingException,
    MappingIfException,
    MappingTransformException,
    NoSuchPropertyException,
    RuntimeException,
    UnmappedObjectException
};
use Danilovl\ObjectDtoMapper\Metadata\{
    MetadataFactory,
    ParameterMetadata
};
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionEnum;
use Symfony\Component\PropertyAccess\Exception\{
    AccessException,
    UnexpectedTypeException
};
use Symfony\Component\PropertyAccess\{
    PropertyAccess,
    PropertyAccessorInterface,
    PropertyPathInterface
};
use Throwable;
use TypeError;
use UnitEnum;

class ObjectToDtoMapper implements ObjectToDtoMapperInterface
{
    public const int DEFAULT_MAX_DEPTH = 32;

    private readonly PropertyAccessorInterface $accessor;

    private readonly CallableResolver $callableResolver;

    private readonly MetadataFactory $metadataFactory;

    /**
     * @var array<string, bool>
     */
    private array $mappableClassCache = [];

    public function __construct(
        ?ContainerInterface $container = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        private readonly bool $allowObjectPassthrough = false,
        private readonly int $maxDepth = self::DEFAULT_MAX_DEPTH
    ) {
        if ($maxDepth < 1) {
            throw new RuntimeException(sprintf('Max depth must be greater than 0, %d given', $maxDepth));
        }

        $this->accessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->callableResolver = new CallableResolver($container);
        $this->metadataFactory = new MetadataFactory($this->callableResolver);
    }

    /**
     * @template T of object
     * @param class-string<T> $target
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     * @return T
     * @throws MappingException
     */
    public function map(object $source, string $target, array $allowGroups = [], array $ignoreGroups = []): object
    {
        $context = new MappingContext($allowGroups, $ignoreGroups, $this->maxDepth);

        return $this->mapObject($source, $target, $context);
    }

    /**
     * @template T of object
     * @param iterable<mixed, object|null> $collection
     * @param class-string<T> $target
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     * @return array<array-key, T|null>
     * @throws MappingException
     */
    public function mapCollection(iterable $collection, string $target, array $allowGroups = [], array $ignoreGroups = []): array
    {
        $this->metadataFactory->getMetadata($target);
        $context = new MappingContext($allowGroups, $ignoreGroups, $this->maxDepth);

        /** @var array<array-key, T|null> $result */
        $result = $this->mapItems($collection, $target, $context);

        return $result;
    }

    /**
     * @template T of object
     * @param class-string<T> $target
     * @return T
     */
    private function mapObject(object $source, string $target, MappingContext $context): object
    {
        $metadata = $this->metadataFactory->getMetadata($target);

        $context->enter($source, $target);

        try {
            $args = [];
            foreach ($metadata->parameters as $parameter) {
                $context->pushPath($parameter->name);

                try {
                    $value = $this->resolveArgument($parameter, $source, $target, $context);
                } finally {
                    $context->popPath();
                }

                if ($parameter->isVariadic) {
                    /** @var array<mixed> $value */
                    array_push($args, ...array_values($value));
                } else {
                    $args[] = $value;
                }
            }

            try {
                /** @var T $instance */
                $instance = $metadata->reflection->newInstanceArgs($args);

                return $instance;
            } catch (TypeError $exception) {
                $message = sprintf('Cannot create "%s": %s%s', $target, $exception->getMessage(), $context->describePath());

                throw new RuntimeException($message, 0, $exception);
            }
        } finally {
            $context->leave($source, $target);
        }
    }

    private function resolveArgument(ParameterMetadata $parameter, object $source, string $target, MappingContext $context): mixed
    {
        $violation = $context->getGroupFilterViolation($parameter->groups);
        if ($violation !== null) {
            $message = sprintf('Non-nullable property "%s" of "%s" is restricted by %s filter', $parameter->name, $target, $violation);

            return $this->fallback($parameter, RuntimeException::class, $message, $context);
        }

        if ($parameter->ifConstant === false) {
            return $this->fallback($parameter, MappingIfException::class, $this->ifSkippedMessage($parameter, $target), $context);
        }

        [$readable, $value] = $this->readValue($source, $parameter->sourcePath);
        if (!$readable) {
            $message = sprintf(
                'Non-nullable property "%s" of "%s" has no default value and source path "%s" cannot be read from "%s"',
                $parameter->name,
                $target,
                (string) $parameter->sourcePath,
                $source::class
            );

            return $this->fallback($parameter, NoSuchPropertyException::class, $message, $context);
        }

        if (!$this->shouldMap($parameter, $value, $source, $target, $context)) {
            return $this->fallback($parameter, MappingIfException::class, $this->ifSkippedMessage($parameter, $target), $context);
        }

        $transformed = false;
        if ($parameter->transformCallable !== null) {
            $value = $this->invokeHandler($parameter->transformCallable, CallableResolver::TYPE_TRANSFORM, $value, $source, $target, $parameter, $context);
            $transformed = true;
        } elseif ($parameter->transformContainer !== null) {
            $callable = $this->callableResolver->resolveTransformContainer($parameter->transformContainer);
            $value = $this->invokeHandler($callable, CallableResolver::TYPE_TRANSFORM, $value, $source, $target, $parameter, $context);
            $transformed = true;
        }

        if ($parameter->isVariadic) {
            return $this->resolveVariadic($value, $parameter, $target, $context, $transformed);
        }

        if ($value === null) {
            if ($parameter->allowsNull) {
                return null;
            }

            if ($parameter->hasDefault) {
                return $parameter->getDefaultValue();
            }

            $message = sprintf('Non-nullable property "%s" of "%s" is null%s', $parameter->name, $target, $context->describePath());

            throw new RuntimeException($message);
        }

        return $this->convert($value, $parameter, $target, $context, $transformed);
    }

    /**
     * @return array<mixed>
     */
    private function resolveVariadic(mixed $value, ParameterMetadata $parameter, string $target, MappingContext $context, bool $trusted): array
    {
        if ($value === null) {
            return [];
        }

        $values = is_iterable($value) ? $value : [$value];

        $result = [];
        foreach ($values as $item) {
            $result[] = $item === null ? null : $this->convert($item, $parameter, $target, $context, $trusted);
        }

        return $result;
    }

    /**
     * @param class-string<MappingException> $exceptionClass
     */
    private function fallback(ParameterMetadata $parameter, string $exceptionClass, string $message, MappingContext $context): mixed
    {
        if ($parameter->isVariadic) {
            return [];
        }

        if ($parameter->hasDefault) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull) {
            return null;
        }

        throw new $exceptionClass($message . $context->describePath());
    }

    private function ifSkippedMessage(ParameterMetadata $parameter, string $target): string
    {
        return sprintf('Non-nullable property "%s" of "%s" skipped by Map(if) condition', $parameter->name, $target);
    }

    /**
     * @return array{bool, mixed}
     */
    private function readValue(object $source, PropertyPathInterface $path): array
    {
        try {
            return [true, $this->accessor->getValue($source, $path)];
        } catch (AccessException|UnexpectedTypeException) {
            return [false, null];
        }
    }

    private function shouldMap(ParameterMetadata $parameter, mixed $value, object $source, string $target, MappingContext $context): bool
    {
        if ($parameter->ifCallable !== null) {
            return (bool) $this->invokeHandler($parameter->ifCallable, CallableResolver::TYPE_IF, $value, $source, $target, $parameter, $context);
        }

        if ($parameter->ifContainer !== null) {
            $callable = $this->callableResolver->resolveIfContainer($parameter->ifContainer);

            return (bool) $this->invokeHandler($callable, CallableResolver::TYPE_IF, $value, $source, $target, $parameter, $context);
        }

        return true;
    }

    /**
     * @param CallableResolver::TYPE_* $type
     */
    private function invokeHandler(
        ResolvedCallable $callable,
        string $type,
        mixed $value,
        object $source,
        string $target,
        ParameterMetadata $parameter,
        MappingContext $context
    ): mixed {
        try {
            return $callable($value, $source, $target);
        } catch (MappingException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $message = sprintf(
                'Map(%s) handler failed for property "%s" of "%s": %s%s',
                $type,
                $parameter->name,
                $target,
                $exception->getMessage(),
                $context->describePath()
            );

            throw $type === CallableResolver::TYPE_IF
                ? new MappingIfException($message, 0, $exception)
                : new MappingTransformException($message, 0, $exception);
        }
    }

    private function convert(mixed $value, ParameterMetadata $parameter, string $target, MappingContext $context, bool $trusted): mixed
    {
        if ($parameter->mapTarget !== null) {
            if (is_iterable($value)) {
                return $this->mapItems($value, $parameter->mapTarget, $context);
            }

            if (is_object($value)) {
                return $value instanceof $parameter->mapTarget ? $value : $this->mapObject($value, $parameter->mapTarget, $context);
            }
        }

        if (is_object($value)) {
            foreach ($parameter->classTypes as $classType) {
                if ($value instanceof $classType) {
                    return $value;
                }
            }
        }

        if ($value instanceof DateTimeInterface) {
            return $this->convertDateTime($value, $parameter);
        }

        if ($value instanceof UnitEnum) {
            return $this->convertEnum($value, $parameter);
        }

        if ((is_int($value) || is_string($value)) && !$parameter->isLoose() && !$parameter->acceptsBuiltin(get_debug_type($value))) {
            foreach ($parameter->classTypes as $classType) {
                if (is_subclass_of($classType, BackedEnum::class)) {
                    return $this->toBackedEnum($value, $classType, $parameter, $target, $context);
                }
            }
        }

        if (is_iterable($value) && ($parameter->acceptsBuiltin('array') || $parameter->acceptsBuiltin('iterable'))) {
            $array = is_array($value) ? $value : $this->iterableToArray($value);
            if (!$trusted) {
                $this->assertNoUnmappedObjects($array, $parameter, $target, $context);
            }

            return $array;
        }

        if (is_object($value)) {
            foreach ($parameter->classTypes as $classType) {
                if ($this->isMappableClass($classType)) {
                    return $this->mapObject($value, $classType, $context);
                }
            }

            if (count($parameter->classTypes) > 0 && !$parameter->isLoose() && !$parameter->acceptsBuiltin('object')) {
                $message = sprintf(
                    'Cannot map object of class "%s" to property "%s" of "%s": type "%s" is not a concrete DTO class. Declare a concrete DTO type or use #[Map(target: ...)]%s',
                    $value::class,
                    $parameter->name,
                    $target,
                    implode('|', $parameter->classTypes),
                    $context->describePath()
                );

                throw new RuntimeException($message);
            }
        }

        if (!$trusted) {
            if (is_object($value)) {
                $this->assertSafeObject($value, $parameter, $target, $context);
            } elseif (is_array($value)) {
                $this->assertNoUnmappedObjects($value, $parameter, $target, $context);
            }
        }

        return $value;
    }

    private function convertDateTime(DateTimeInterface $value, ParameterMetadata $parameter): mixed
    {
        foreach ($parameter->classTypes as $classType) {
            if (is_a($classType, DateTimeImmutable::class, true)) {
                return $classType::createFromInterface($value);
            }

            if (is_a($classType, DateTime::class, true)) {
                return $classType::createFromInterface($value);
            }
        }

        if ($parameter->acceptsBuiltin('string') || $parameter->isLoose()) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return $value;
    }

    private function convertEnum(UnitEnum $value, ParameterMetadata $parameter): mixed
    {
        if ($value instanceof BackedEnum) {
            if ($parameter->acceptsBuiltin(get_debug_type($value->value))) {
                return $value->value;
            }

            if ($parameter->acceptsBuiltin('string')) {
                return (string) $value->value;
            }
        } elseif ($parameter->acceptsBuiltin('string')) {
            return $value->name;
        }

        return $value;
    }

    /**
     * @param class-string $enumClass
     */
    private function toBackedEnum(int|string $value, string $enumClass, ParameterMetadata $parameter, string $target, MappingContext $context): BackedEnum
    {
        /** @var class-string<BackedEnum> $enumClass */
        $backingType = (string) new ReflectionEnum($enumClass)->getBackingType();

        $case = null;
        if ($backingType === 'int') {
            if (is_int($value) || preg_match('/^-?\d+$/', $value) === 1) {
                $case = $enumClass::tryFrom((int) $value);
            }
        } else {
            $case = $enumClass::tryFrom((string) $value);
        }

        if ($case === null) {
            $message = sprintf(
                'Value %s is not a valid backing value for enum "%s" of property "%s" in "%s"%s',
                var_export($value, true),
                $enumClass,
                $parameter->name,
                $target,
                $context->describePath()
            );

            throw new RuntimeException($message);
        }

        return $case;
    }

    /**
     * @param iterable<mixed> $items
     * @return array<mixed>
     */
    private function mapItems(iterable $items, string $target, MappingContext $context): array
    {
        /** @var class-string $target */
        $result = [];
        $hasStringKeys = false;

        foreach ($items as $key => $item) {
            $isValidKey = is_int($key) || is_string($key);
            $context->pushPath($isValidKey ? sprintf('[%s]', $key) : '[?]');

            try {
                if ($item === null) {
                    $mapped = null;
                } elseif (!is_object($item)) {
                    $message = sprintf('Collection item must be an object to be mapped to "%s", %s given%s', $target, get_debug_type($item), $context->describePath());

                    throw new RuntimeException($message);
                } else {
                    $mapped = $item instanceof $target ? $item : $this->mapObject($item, $target, $context);
                }
            } finally {
                $context->popPath();
            }

            if ($isValidKey) {
                $result[$key] = $mapped;
                $hasStringKeys = $hasStringKeys || is_string($key);
            } else {
                $result[] = $mapped;
            }
        }

        return $hasStringKeys ? $result : array_values($result);
    }

    /**
     * @param iterable<mixed> $value
     * @return array<mixed>
     */
    private function iterableToArray(iterable $value): array
    {
        $result = [];
        $hasStringKeys = false;

        foreach ($value as $key => $item) {
            if (is_int($key) || is_string($key)) {
                $result[$key] = $item;
                $hasStringKeys = $hasStringKeys || is_string($key);
            } else {
                $result[] = $item;
            }
        }

        return $hasStringKeys ? $result : array_values($result);
    }

    private function isSafeObject(object $value): bool
    {
        return $this->allowObjectPassthrough || $value instanceof DateTimeInterface || $value instanceof UnitEnum;
    }

    private function assertSafeObject(object $value, ParameterMetadata $parameter, string $target, MappingContext $context, string $key = ''): void
    {
        if ($this->isSafeObject($value)) {
            return;
        }

        $message = sprintf(
            'Refusing to pass unmapped object of class "%s" into property "%s" of "%s"%s%s. Declare a concrete DTO type, use #[Map(target: ...)] or #[Map(transform: ...)], or enable allowObjectPassthrough',
            $value::class,
            $parameter->name,
            $target,
            $key === '' ? '' : sprintf(' at key "%s"', $key),
            $context->describePath()
        );

        throw new UnmappedObjectException($message);
    }

    /**
     * @param array<mixed> $value
     */
    private function assertNoUnmappedObjects(array $value, ParameterMetadata $parameter, string $target, MappingContext $context, string $prefix = ''): void
    {
        if ($this->allowObjectPassthrough) {
            return;
        }

        foreach ($value as $key => $item) {
            $keyPath = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_object($item)) {
                $this->assertSafeObject($item, $parameter, $target, $context, $keyPath);
            } elseif (is_array($item)) {
                $this->assertNoUnmappedObjects($item, $parameter, $target, $context, $keyPath);
            }
        }
    }

    private function isMappableClass(string $class): bool
    {
        if (isset($this->mappableClassCache[$class])) {
            return $this->mappableClassCache[$class];
        }

        $mappable = false;
        if (class_exists($class)) {
            $reflection = new ReflectionClass($class);
            $mappable = !$reflection->isInternal()
                && !$reflection->isEnum()
                && $reflection->isInstantiable()
                && $reflection->getConstructor() !== null;
        }

        return $this->mappableClassCache[$class] = $mappable;
    }
}
