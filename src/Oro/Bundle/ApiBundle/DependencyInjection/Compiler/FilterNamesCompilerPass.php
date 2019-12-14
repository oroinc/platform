<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all filter names providers.
 */
class FilterNamesCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerRequestTypeDependedTaggedServices(
            $container,
            'oro_api.filter_names_registry',
            'oro.api.filter_names'
        );
    }
}
