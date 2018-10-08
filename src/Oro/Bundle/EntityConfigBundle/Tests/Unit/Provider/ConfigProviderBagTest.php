<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Component\DependencyInjection\ServiceLink;

class ConfigProviderBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configBag;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManagerLink;

    /** @var ConfigProviderBag */
    protected $configProviderBag;

    protected function setUp()
    {
        $this->configBag = $this->getMockBuilder(PropertyConfigBag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManagerLink = $this->getMockBuilder(ServiceLink::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProviderBag = new ConfigProviderBag(['scope1'], $this->configManagerLink, $this->configBag);
    }

    public function testGetProviderForExistingScope()
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManagerLink->expects(self::once())
            ->method('getService')
            ->willReturn($configManager);

        $provider = $this->configProviderBag->getProvider('scope1');
        self::assertInstanceOf(ConfigProvider::class, $provider);
        $providers = self::getObjectAttribute($this->configProviderBag, 'providers');
        self::assertArrayHasKey('scope1', $providers);
        self::assertSame($provider, $providers['scope1']);

        // test that cached provider is returned
        self::assertSame($provider, $this->configProviderBag->getProvider('scope1'));
    }

    public function testGetProviderForNotExistingScope()
    {
        $this->configManagerLink->expects(self::never())
            ->method('getService');

        self::assertNull($this->configProviderBag->getProvider('scope2'));
        $providers = self::getObjectAttribute($this->configProviderBag, 'providers');
        self::assertArrayNotHasKey('scope2', $providers);
    }

    public function testGetProviders()
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManagerLink->expects(self::once())
            ->method('getService')
            ->willReturn($configManager);

        $providers = $this->configProviderBag->getProviders();
        self::assertCount(1, $providers);
        self::assertArrayHasKey('scope1', $providers);
        self::assertInstanceOf(ConfigProvider::class, $providers['scope1']);

        // test that cached providers are returned
        $cachedProviders = $this->configProviderBag->getProviders();
        self::assertCount(1, $cachedProviders);
        self::assertArrayHasKey('scope1', $cachedProviders);
        self::assertSame($providers['scope1'], $cachedProviders['scope1']);
    }
}
