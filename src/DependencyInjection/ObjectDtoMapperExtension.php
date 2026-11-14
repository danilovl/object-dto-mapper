<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ObjectDtoMapperExtension extends Extension
{
    public const string HANDLER_TAG = 'danilovl.object_dto_mapper.handler';

    public const string PARAMETER_ALLOW_OBJECT_PASSTHROUGH = 'danilovl.object_dto_mapper.allow_object_passthrough';

    public const string PARAMETER_MAX_DEPTH = 'danilovl.object_dto_mapper.max_depth';

    private const string DIR_CONFIG = '/../Resources/config';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration, $configs);

        $container->setParameter(self::PARAMETER_ALLOW_OBJECT_PASSTHROUGH, $config['allow_object_passthrough']);
        $container->setParameter(self::PARAMETER_MAX_DEPTH, $config['max_depth']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . self::DIR_CONFIG));
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
