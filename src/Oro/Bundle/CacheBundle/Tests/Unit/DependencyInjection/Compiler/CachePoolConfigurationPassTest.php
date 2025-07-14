<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CachePoolConfigurationPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CachePoolConfigurationPassTest extends TestCase
{
    public function testDefaultCachePoolDefinitions(): void
    {
        $container = new ContainerBuilder();

        $cacheDef1 = new Definition(FilesystemAdapter::class);
        $cacheDef1->setTags(['cache.pool' => 'filesystem']);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_POOL_WITHOUT_MEMORY_CACHE, $cacheDef1);

        $cacheDef2 = new Definition(RedisAdapter::class);
        $cacheDef2->setTags(['cache.pool' => 'redis']);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_POOL, $cacheDef2);

        $compiler = new CachePoolConfigurationPass();
        $compiler->process($container);

        self::assertEmpty(
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_POOL_WITHOUT_MEMORY_CACHE)->getTags()
        );
        self::assertEmpty(
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_POOL)->getTags()
        );
    }
}
