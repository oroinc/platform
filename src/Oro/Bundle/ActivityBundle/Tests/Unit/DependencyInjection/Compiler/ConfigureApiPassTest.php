<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ActivityBundle\DependencyInjection\Compiler\ConfigureApiPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigureApiPassTest extends \PHPUnit\Framework\TestCase
{
    public function testApiCacheManagerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $apiCacheManagerDef = $container->register('oro_api.cache_manager');

        $compiler = new ConfigureApiPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addResettableService', [new Reference('oro_activity.api.activity_association_provider')]]
            ],
            $apiCacheManagerDef->getMethodCalls()
        );
    }
}
