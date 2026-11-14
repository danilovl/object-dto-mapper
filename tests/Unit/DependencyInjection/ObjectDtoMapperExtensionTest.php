<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Unit\DependencyInjection;

use Danilovl\ObjectDtoMapper\Attribute\{
    MapIfCallableInterface,
    MapTransformCallableInterface
};
use Danilovl\ObjectDtoMapper\DependencyInjection\Compiler\MapHandlerCompilerPass;
use Danilovl\ObjectDtoMapper\DependencyInjection\{
    Configuration,
    ObjectDtoMapperExtension
};
use Danilovl\ObjectDtoMapper\ObjectDtoMapperBundle;
use Danilovl\ObjectDtoMapper\Service\{
    ObjectToDtoMapper,
    ObjectToDtoMapperInterface
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\{ContainerBuilder, ContainerInterface, Reference};

final class ObjectDtoMapperExtensionTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    private function load(array $configs = []): ContainerBuilder
    {
        $container = new ContainerBuilder;
        new ObjectDtoMapperExtension()->load($configs, $container);

        return $container;
    }

    public function testBundleReturnsExtension(): void
    {
        $extension = new ObjectDtoMapperBundle()->getContainerExtension();

        $this->assertInstanceOf(ObjectDtoMapperExtension::class, $extension);
        $this->assertSame(Configuration::ALIAS, $extension->getAlias());
    }

    public function testMapperServiceAndInterfaceAliasAreRegistered(): void
    {
        $container = $this->load();

        $definition = $container->getDefinition(ObjectToDtoMapper::class);
        $this->assertTrue($definition->isPublic());

        $alias = $container->getAlias(ObjectToDtoMapperInterface::class);
        $this->assertSame(ObjectToDtoMapper::class, (string) $alias);
        $this->assertTrue($alias->isPublic());
    }

    public function testMapperArguments(): void
    {
        $arguments = $this->load()->getDefinition(ObjectToDtoMapper::class)->getArguments();

        $this->assertInstanceOf(ServiceLocatorArgument::class, $arguments['$container']);
        $this->assertSame(ObjectDtoMapperExtension::HANDLER_TAG, $arguments['$container']->getTaggedIteratorArgument()?->getTag());

        $propertyAccessor = $arguments['$propertyAccessor'];
        $this->assertInstanceOf(Reference::class, $propertyAccessor);
        $this->assertSame('property_accessor', (string) $propertyAccessor);
        $this->assertSame(ContainerInterface::IGNORE_ON_INVALID_REFERENCE, $propertyAccessor->getInvalidBehavior());

        $this->assertSame('%' . ObjectDtoMapperExtension::PARAMETER_ALLOW_OBJECT_PASSTHROUGH . '%', $arguments['$allowObjectPassthrough']);
        $this->assertSame('%' . ObjectDtoMapperExtension::PARAMETER_MAX_DEPTH . '%', $arguments['$maxDepth']);
    }

    public function testBundleRegistersHandlerCompilerPass(): void
    {
        $container = new ContainerBuilder;
        new ObjectDtoMapperBundle()->build($container);

        $passes = array_map(
            static fn (object $pass): string => $pass::class,
            $container->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );

        $this->assertContains(MapHandlerCompilerPass::class, $passes);
    }

    public function testExtensionDoesNotRelyOnAutoconfiguration(): void
    {
        $autoconfigured = $this->load()->getAutoconfiguredInstanceof();

        $this->assertArrayNotHasKey(MapIfCallableInterface::class, $autoconfigured);
        $this->assertArrayNotHasKey(MapTransformCallableInterface::class, $autoconfigured);
    }

    public function testDefaultParameters(): void
    {
        $container = $this->load();

        $this->assertFalse($container->getParameter(ObjectDtoMapperExtension::PARAMETER_ALLOW_OBJECT_PASSTHROUGH));
        $this->assertSame(ObjectToDtoMapper::DEFAULT_MAX_DEPTH, $container->getParameter(ObjectDtoMapperExtension::PARAMETER_MAX_DEPTH));
    }

    public function testCustomParameters(): void
    {
        $container = $this->load([['allow_object_passthrough' => true, 'max_depth' => 5]]);

        $this->assertTrue($container->getParameter(ObjectDtoMapperExtension::PARAMETER_ALLOW_OBJECT_PASSTHROUGH));
        $this->assertSame(5, $container->getParameter(ObjectDtoMapperExtension::PARAMETER_MAX_DEPTH));
    }

    public function testInvalidMaxDepth(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load([['max_depth' => 0]]);
    }
}
