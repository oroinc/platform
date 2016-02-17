<?php

namespace Oro\Bundle\DistributionBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\DistributionBundle\DependencyInjection\Compiler\HiddenRoutesPass;
use Oro\Bundle\DistributionBundle\DependencyInjection\Compiler\RoutingOptionsResolverPass;

class OroDistributionBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RoutingOptionsResolverPass());
        $container->addCompilerPass(new HiddenRoutesPass());
    }
}
