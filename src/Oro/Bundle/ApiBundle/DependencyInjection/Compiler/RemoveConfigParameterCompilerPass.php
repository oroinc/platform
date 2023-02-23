<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes the temporary container parameter that is used to share ApiBundle configuration.
 * @see \Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension::load
 */
class RemoveConfigParameterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        DependencyInjectionUtil::removeConfig($container);
    }
}
