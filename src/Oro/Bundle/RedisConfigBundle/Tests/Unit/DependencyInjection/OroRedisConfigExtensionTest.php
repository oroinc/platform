<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RedisConfigBundle\DependencyInjection\OroRedisConfigExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroRedisConfigExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;
    private OroRedisConfigExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroRedisConfigExtension();
    }

    /**
     * @dataProvider loadCacheParameterDataProvider
     */
    public function testLoad(string $paramId, mixed $paramValue): void
    {
        $this->container->setParameter($paramId, $paramValue);

        $this->extension->load([], $this->container);

        $this->assertNotEmpty($this->container->getResources());
    }

    public function loadCacheParameterDataProvider(): array
    {
        return [
            ['redis_dsn_cache','redis://127.0.0.1:6379/0'],
            ['redis_dsn_doctrine','redis://127.0.0.1:6379/1'],
            ['redis_dsn_layout','redis://127.0.0.1:6379/2']
        ];
    }

    public function testPrependConfigCacheEnadbled(): void
    {
        $this->container->setParameter('redis_dsn_cache', 'redis://127.0.0.1:6379/0');

        $this->extension->prepend($this->container);

        $config = $this->container->getExtensionConfig('framework');
        $this->assertEquals('oro.cache.redis_provider', $config[0]['cache']['default_redis_provider']);
    }

    public function testPrependConfigLayoutCacheEnabled(): void
    {
        $this->container->setParameter('redis_dsn_layout', 'redis://127.0.0.1:6379/2');

        $this->extension->prepend($this->container);

        $config = $this->container->getExtensionConfig('framework');
        $renderCache = $config[0]['cache']['pools']['cache.oro_layout.render'];
        $this->assertEquals('cache.adapter.redis_tag_aware', $renderCache['adapter']);
        $this->assertEquals('oro.cache.layout.redis_provider', $renderCache['provider']);
    }
}
