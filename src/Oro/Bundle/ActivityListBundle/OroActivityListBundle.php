<?php

namespace Oro\Bundle\ActivityListBundle;

use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\ActivityListProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroActivityListBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ActivityListProviderPass());
    }
}
