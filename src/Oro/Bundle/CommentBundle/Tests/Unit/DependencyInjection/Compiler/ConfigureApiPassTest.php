<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CommentBundle\DependencyInjection\Compiler\ConfigureApiPass;
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
                ['addResettableService', [new Reference('oro_comment.api.comment_association_provider')]]
            ],
            $apiCacheManagerDef->getMethodCalls()
        );
    }
}
