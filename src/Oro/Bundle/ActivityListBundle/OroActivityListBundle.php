<?php

namespace Oro\Bundle\ActivityListBundle;

use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\ActivityListProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
