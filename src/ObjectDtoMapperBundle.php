<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper;

use Danilovl\ObjectDtoMapper\DependencyInjection\Compiler\MapHandlerCompilerPass;
use Danilovl\ObjectDtoMapper\DependencyInjection\ObjectDtoMapperExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ObjectDtoMapperBundle extends Bundle
{
    public function getContainerExtension(): ObjectDtoMapperExtension
    {
        return new ObjectDtoMapperExtension;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new MapHandlerCompilerPass);
    }
}
