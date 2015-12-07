<?php

namespace Oro\Bundle\ActivityListBundle;

use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\AddStrategyCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\ActivityListProvidersPass;

class OroActivityListBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ActivityListProvidersPass());
    }
}
