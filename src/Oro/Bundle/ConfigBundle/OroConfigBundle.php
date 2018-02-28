<?php

namespace Oro\Bundle\ConfigBundle;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\ListenerExcludeConfigConnectionPass;
use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;
use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationSearchPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroConfigBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SystemConfigurationPass());
        $container->addCompilerPass(new SystemConfigurationSearchPass());
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new ListenerExcludeConfigConnectionPass());
            $container->moveCompilerPassBefore(
                'Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\ListenerExcludeConfigConnectionPass',
                'Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass'
            );
        }
    }
}
