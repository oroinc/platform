<?php

namespace Oro\Bundle\SyncBundle;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\WebsocketRouterConfigurationPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\SkipTagTrackingPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\TagGeneratorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSyncBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TagGeneratorPass());
        $container->addCompilerPass(new SkipTagTrackingPass());
        $container->addCompilerPass(new WebsocketRouterConfigurationPass());
    }
}
