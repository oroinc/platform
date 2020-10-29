<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle;

use Oro\Bundle\MicrosoftIntegrationBundle\DependencyInjection\Compiler\Office365ResourceOwnerConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The MicrosoftIntegrationBundle bundle class.
 */
class OroMicrosoftIntegrationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Office365ResourceOwnerConfigurationPass());
    }
}
