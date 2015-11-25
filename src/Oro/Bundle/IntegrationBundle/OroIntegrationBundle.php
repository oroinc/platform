<?php

namespace Oro\Bundle\IntegrationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\TypesPass;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\SettingsPass;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\DeleteIntegrationProvidersPass;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\ProcessorsPass;

class OroIntegrationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TypesPass());
        $container->addCompilerPass(new DeleteIntegrationProvidersPass());
        $container->addCompilerPass(new SettingsPass());
        $container->addCompilerPass(new ProcessorsPass());
    }
}
