<?php

namespace Oro\Bundle\ActivityListBundle;

use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\ActivityListProviderPass;
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

        $container->addCompilerPass(new ActivityListProviderPass());
    }
}
