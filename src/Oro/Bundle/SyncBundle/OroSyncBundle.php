<?php

namespace Oro\Bundle\SyncBundle;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\SkipTagTrackingPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\WebsocketOriginRegistryPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\WebsocketRouterCachePass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\WebsocketRouterConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSyncBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SkipTagTrackingPass());
        $container->addCompilerPass(new WebsocketRouterConfigurationPass());
        $container->addCompilerPass(new WebsocketRouterCachePass());
        $container->addCompilerPass(new WebsocketOriginRegistryPass());
    }
}
