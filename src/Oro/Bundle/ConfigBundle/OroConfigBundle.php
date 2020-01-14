<?php

namespace Oro\Bundle\ConfigBundle;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\ListenerExcludeConfigConnectionPass;
use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The ConfigBundle bundle class.
 */
class OroConfigBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SystemConfigurationPass());
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new ListenerExcludeConfigConnectionPass());
            $container->moveCompilerPassBefore(
                ListenerExcludeConfigConnectionPass::class,
                UpdateDoctrineEventHandlersPass::class
            );
        }
    }
}
