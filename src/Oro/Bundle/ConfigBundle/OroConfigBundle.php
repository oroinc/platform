<?php

namespace Oro\Bundle\ConfigBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\ListenerExcludeConfigConnectionPass;
use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class OroConfigBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SystemConfigurationPass());
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new ListenerExcludeConfigConnectionPass());
            $container->moveCompilerPassBefore(
                'Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\ListenerExcludeConfigConnectionPass',
                'Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass'
            );
        }
    }
}
