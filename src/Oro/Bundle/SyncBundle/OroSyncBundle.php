<?php

namespace Oro\Bundle\SyncBundle;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\OriginProviderPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\SkipTagTrackingPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\TagGeneratorPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\WebsocketRouterConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Sync/WebSocket functionality
 */
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
        $container->addCompilerPass(new OriginProviderPass());
    }
}
