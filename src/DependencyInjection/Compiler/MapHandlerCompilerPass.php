<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\DependencyInjection\Compiler;

use Danilovl\ObjectDtoMapper\Attribute\{
    MapIfCallableInterface,
    MapTransformCallableInterface
};
use Danilovl\ObjectDtoMapper\DependencyInjection\ObjectDtoMapperExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\{
    ChildDefinition,
    ContainerBuilder,
    Definition
};
use Throwable;

class MapHandlerCompilerPass implements CompilerPassInterface
{
    private const array HANDLER_INTERFACES = [
        MapIfCallableInterface::class,
        MapTransformCallableInterface::class
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract() || $definition->isSynthetic() || $definition->hasTag(ObjectDtoMapperExtension::HANDLER_TAG)) {
                continue;
            }

            $class = $this->resolveClass($container, $id, $definition);
            if ($class === null) {
                continue;
            }

            $reflection = $container->getReflectionClass($class, false);
            if ($reflection === null) {
                continue;
            }

            foreach (self::HANDLER_INTERFACES as $interface) {
                if ($reflection->implementsInterface($interface)) {
                    $definition->addTag(ObjectDtoMapperExtension::HANDLER_TAG);

                    break;
                }
            }
        }
    }

    private function resolveClass(ContainerBuilder $container, string $id, Definition $definition): ?string
    {
        $visited = [];

        while (true) {
            $class = $definition->getClass();
            if ($class !== null) {
                try {
                    $class = $container->getParameterBag()->resolveValue($class);
                } catch (Throwable) {
                    return null;
                }

                return is_string($class) && $class !== '' ? $class : null;
            }

            if (!$definition instanceof ChildDefinition) {
                return class_exists($id) ? $id : null;
            }

            $parent = $definition->getParent();
            if (isset($visited[$parent]) || !$container->hasDefinition($parent)) {
                return null;
            }

            $visited[$parent] = true;
            $definition = $container->getDefinition($parent);
        }
    }
}
