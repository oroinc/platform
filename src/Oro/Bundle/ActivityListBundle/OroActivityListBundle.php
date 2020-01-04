<?php

namespace Oro\Bundle\ActivityListBundle;

use Oro\Component\DependencyInjection\Compiler\InverseNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The ActivityListBundle bundle class.
 */
class OroActivityListBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InverseNamedTaggedServiceCompilerPass(
            'oro_activity_list.provider.chain',
            'oro_activity_list.provider',
            'class'
        ));
    }
}
