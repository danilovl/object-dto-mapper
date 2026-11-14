<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Service;

use Closure;
use Danilovl\ObjectDtoMapper\Attribute\{
    MapIfCallableInterface,
    MapTransformCallableInterface
};
use Danilovl\ObjectDtoMapper\Exception\{
    MappingException,
    MappingIfException,
    MappingTransformException,
    NoSuchClassException
};
use Psr\Container\ContainerInterface;
use Throwable;

final class CallableResolver
{
    public const string TYPE_IF = 'if';

    public const string TYPE_TRANSFORM = 'transform';

    /**
     * @var array<string, ResolvedCallable>
     */
    private array $containerCache = [];

    public function __construct(private readonly ?ContainerInterface $container = null) {}

    /**
     * @param self::TYPE_* $type
     */
    public function resolve(mixed $definition, string $type, string $property): ResolvedCallable
    {
        if ($definition instanceof Closure) {
            return new ResolvedCallable($definition);
        }

        if ((is_string($definition) || is_array($definition)) && is_callable($definition)) {
            return new ResolvedCallable(Closure::fromCallable($definition));
        }

        if (is_string($definition) && $definition !== '') {
            return $this->resolveInvokableClass($definition, $type, $property);
        }

        if (is_array($definition)) {
            $message = sprintf(
                'Map(%s) definition for property "%s" is not a valid callable: array definitions must reference an existing public static method [Class::class, "method"]',
                $type,
                $property
            );

            throw $this->definitionException($type, $message);
        }

        $message = sprintf('Map(%s) definition for property "%s" is not callable', $type, $property);

        throw $this->definitionException($type, $message);
    }

    public function resolveIfContainer(string $serviceId): ResolvedCallable
    {
        return $this->resolveFromContainer($serviceId, 'Map(if) container', MapIfCallableInterface::class, MappingIfException::class);
    }

    public function resolveTransformContainer(string $serviceId): ResolvedCallable
    {
        return $this->resolveFromContainer($serviceId, 'Map(transformContainer)', MapTransformCallableInterface::class, MappingTransformException::class);
    }

    /**
     * @param self::TYPE_* $type
     */
    private function resolveInvokableClass(string $class, string $type, string $property): ResolvedCallable
    {
        if ($this->container?->has($class)) {
            $service = $this->getService($class, $type === self::TYPE_IF ? MappingIfException::class : MappingTransformException::class);
        } else {
            if (!class_exists($class)) {
                $message = sprintf('Map(%s) definition error: Class "%s" does not exist for property %s', $type, $class, $property);

                throw new NoSuchClassException($message);
            }

            try {
                $service = new $class;
            } catch (Throwable $exception) {
                $message = sprintf(
                    'Map(%s) definition "%s" for property %s cannot be instantiated without arguments; register it as a container service: %s',
                    $type,
                    $class,
                    $property,
                    $exception->getMessage()
                );

                throw $this->definitionException($type, $message, $exception);
            }
        }

        if (!is_callable($service)) {
            $message = sprintf('Map(%s) definition "%s" is not a valid callable for property %s', $type, $class, $property);

            throw $this->definitionException($type, $message);
        }

        return new ResolvedCallable(Closure::fromCallable($service));
    }

    /**
     * @param class-string $interface
     * @param class-string<MappingException> $exceptionClass
     */
    private function resolveFromContainer(string $serviceId, string $label, string $interface, string $exceptionClass): ResolvedCallable
    {
        $cacheKey = $label . '|' . $serviceId;
        if (isset($this->containerCache[$cacheKey])) {
            return $this->containerCache[$cacheKey];
        }

        if ($this->container === null) {
            $message = sprintf('%s service not found: "%s" (no container configured in the mapper)', $label, $serviceId);

            throw new $exceptionClass($message);
        }

        if (!$this->container->has($serviceId)) {
            $message = sprintf('%s service not found: "%s"', $label, $serviceId);

            throw new $exceptionClass($message);
        }

        $service = $this->getService($serviceId, $exceptionClass);

        if (!$service instanceof $interface || !is_callable($service)) {
            $message = sprintf('%s "%s" is not a valid callable service: it must implement %s', $label, $serviceId, $interface);

            throw new $exceptionClass($message);
        }

        return $this->containerCache[$cacheKey] = new ResolvedCallable(Closure::fromCallable($service));
    }

    /**
     * @param class-string<MappingException> $exceptionClass
     */
    private function getService(string $serviceId, string $exceptionClass): mixed
    {
        try {
            return $this->container?->get($serviceId);
        } catch (Throwable $exception) {
            $message = sprintf('Service "%s" cannot be retrieved from container: %s', $serviceId, $exception->getMessage());

            throw new $exceptionClass($message, 0, $exception);
        }
    }

    /**
     * @param self::TYPE_* $type
     */
    private function definitionException(string $type, string $message, ?Throwable $previous = null): MappingException
    {
        return $type === self::TYPE_IF
            ? new MappingIfException($message, 0, $previous)
            : new MappingTransformException($message, 0, $previous);
    }
}
