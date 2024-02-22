<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\Validator\EntityValidationLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds the entity validation loader to the end of the validation loaders chain.
 */
class EntityValidationLoaderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $loaderServiceId = 'oro_entity.entity_validation_loader';
        $container->register($loaderServiceId, EntityValidationLoader::class)
            ->addArgument(new Reference('doctrine'));
        $container->getDefinition('validator.builder')
            ->addMethodCall('addLoader', [new Reference($loaderServiceId)]);
    }
}
