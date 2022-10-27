<?php

namespace Oro\Bundle\SSOBundle;

use Oro\Bundle\SSOBundle\DependencyInjection\Compiler\HwiConfigurationPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSSOBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new HwiConfigurationPass());
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_sso.oauth_user_provider',
            'oro.sso.oauth_user_provider',
            'resource_owner'
        ));
    }
}
