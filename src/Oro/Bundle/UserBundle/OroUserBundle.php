<?php

namespace Oro\Bundle\UserBundle;

use Oro\Bundle\UserBundle\DependencyInjection\Compiler\EscapeWsseConfigurationPass;
use Oro\Bundle\UserBundle\DependencyInjection\Compiler\PrivilegeCategoryPass;
use Oro\Bundle\UserBundle\DependencyInjection\Compiler\SecurityFirewallCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroUserBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new EscapeWsseConfigurationPass());
        $container->addCompilerPass(new PrivilegeCategoryPass());
        $container->addCompilerPass(new SecurityFirewallCompilerPass());
    }
}
