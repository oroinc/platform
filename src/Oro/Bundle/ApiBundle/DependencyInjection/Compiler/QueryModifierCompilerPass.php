<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all query modifiers.
 */
class QueryModifierCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerRequestTypeDependedTaggedServices(
            $container,
            'oro_api.entity_serializer.query_modifier_registry',
            'oro.api.query_modifier'
        );
    }
}
