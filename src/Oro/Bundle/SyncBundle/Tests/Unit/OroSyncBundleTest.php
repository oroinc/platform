<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\OriginProviderPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\PubSubRouterCachePass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\SkipTagTrackingPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\TagGeneratorPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\WebsocketRouterConfigurationPass;
use Oro\Bundle\SyncBundle\OroSyncBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSyncBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects($this->exactly(5))
            ->method('addCompilerPass')
            ->withConsecutive(
                [new TagGeneratorPass()],
                [new SkipTagTrackingPass()],
                [new WebsocketRouterConfigurationPass()],
                [new OriginProviderPass()],
                [new PubSubRouterCachePass()]
            );

        $bundle = new OroSyncBundle();
        $bundle->build($containerBuilder);
    }
}
