<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle;

use Oro\Bundle\MicrosoftIntegrationBundle\DependencyInjection\Compiler\Office365ResourceOwnerConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroMicrosoftIntegrationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new Office365ResourceOwnerConfigurationPass());
    }
}
