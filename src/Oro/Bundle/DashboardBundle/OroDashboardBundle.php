<?php

namespace Oro\Bundle\DashboardBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Oro\Bundle\DashboardBundle\DependencyInjection\Compiler\ConfigurationPass;

class OroDashboardBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationPass());
    }
}
