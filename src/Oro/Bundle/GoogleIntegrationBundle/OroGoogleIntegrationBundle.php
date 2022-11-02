<?php

namespace Oro\Bundle\GoogleIntegrationBundle;

use Oro\Bundle\GoogleIntegrationBundle\DependencyInjection\Compiler\GoogleResourceOwnerConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroGoogleIntegrationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new GoogleResourceOwnerConfigurationPass());
    }
}
