<?php

namespace Oro\Bundle\IntegrationBundle;

use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\DeleteIntegrationProvidersPass;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\ProcessorsPass;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\TypesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroIntegrationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TypesPass());
        $container->addCompilerPass(new DeleteIntegrationProvidersPass());
        $container->addCompilerPass(new ProcessorsPass());
    }
}
