<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\DependencyInjection;

use Danilovl\ObjectDtoMapper\Service\ObjectToDtoMapper;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const string ALIAS = 'danilovl_object_dto_mapper';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('allow_object_passthrough')
                    ->defaultFalse()
                ->end()
                ->integerNode('max_depth')
                    ->defaultValue(ObjectToDtoMapper::DEFAULT_MAX_DEPTH)
                    ->min(1)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
