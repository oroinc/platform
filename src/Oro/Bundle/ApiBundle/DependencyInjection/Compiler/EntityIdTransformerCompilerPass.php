<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all entity identifier transformers.
 */
class EntityIdTransformerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerRequestTypeDependedTaggedServices(
            $container,
            'oro_api.entity_id_transformer_registry',
            'oro.api.entity_id_transformer'
        );
    }
}
