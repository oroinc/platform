<?php

namespace Oro\Bundle\SSOBundle;

use Oro\Bundle\SSOBundle\DependencyInjection\Compiler\HwiConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSSOBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new HwiConfigurationPass());
    }
}
