<?php

namespace Oro\Bundle\IntegrationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\ChannelPass;

class OroIntegrationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ChannelPass());
    }
}
