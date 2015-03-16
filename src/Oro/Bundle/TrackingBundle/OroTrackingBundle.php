<?php

namespace Oro\Bundle\TrackingBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\TrackingBundle\DependencyInjection\Compiler\TrackingEventIdentificationPass;

class OroTrackingBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TrackingEventIdentificationPass());
    }
}
