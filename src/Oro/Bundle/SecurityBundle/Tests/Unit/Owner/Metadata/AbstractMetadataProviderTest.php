<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;

class AbstractMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    const SOME_CLASS = 'SomeClass';
    const UNDEFINED_CLASS = 'UndefinedClass';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected $cache;

    /**
     * @var OwnershipMetadataProviderStub
     */
    protected $provider;

    /**
     * @var Config
     */
    protected $config;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['delete', 'deleteAll', 'fetch', 'save'])
            ->getMockForAbstractClass();

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_entity_config.provider.ownership',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->configProvider,
                        ],
                        [
                            'oro_security.owner.ownership_metadata_provider.cache',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->cache,
                        ],
                    ]
                )
            );

        $this->provider = new OwnershipMetadataProviderStub($this);
        $this->provider->setContainer($container);

        $this->config = new Config(new EntityConfigId('ownership', self::SOME_CLASS));
    }

    protected function tearDown()
    {
        unset($this->configProvider, $this->cache, $this->provider, $this->config);
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::SOME_CLASS);

        $this->provider->clearCache(self::SOME_CLASS);
    }

    public function testClearCacheAll()
    {
        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->provider->clearCache();
    }

    public function testGetMetadataWithoutCache()
    {
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOME_CLASS)
            ->willReturn($this->config);

        $this->cache = null;

        $this->assertEquals(new OwnershipMetadata(), $this->provider->getMetadata(self::SOME_CLASS));
    }

    public function testGetMetadataUndefinedClassWithCache()
    {
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::UNDEFINED_CLASS)
            ->willReturn(false);
        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->cache->expects($this->at(0))
            ->method('fetch')
            ->with(self::UNDEFINED_CLASS)
            ->willReturn(false);
        $this->cache->expects($this->at(2))
            ->method('fetch')
            ->with(self::UNDEFINED_CLASS)
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(self::UNDEFINED_CLASS, true);

        $metadata = new OwnershipMetadata();
        $providerWithCleanCache = clone $this->provider;

        // no cache
        $this->assertEquals($metadata, $this->provider->getMetadata(self::UNDEFINED_CLASS));
        // local cache
        $this->assertEquals($metadata, $this->provider->getMetadata(self::UNDEFINED_CLASS));
        // cache
        $this->assertEquals($metadata, $providerWithCleanCache->getMetadata(self::UNDEFINED_CLASS));
    }

    public function testWarmUpCacheWithoutClassName()
    {
        $configs = [$this->config];

        $this->configProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn($configs);
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOME_CLASS)
            ->willReturn($this->config);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::SOME_CLASS)
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(self::SOME_CLASS);

        $this->provider->warmUpCache();
    }

    public function testWarmUpCacheWithClassName()
    {
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOME_CLASS)
            ->willReturn($this->config);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::SOME_CLASS)
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(self::SOME_CLASS);

        $this->provider->warmUpCache(self::SOME_CLASS);
    }
}
