<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Config\DefaultCurrencyConfigProvider;

class DefaultCurrencyConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    protected $configManager;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->configManager->method('get')
            ->with('oro_currency.default_currency')
            ->willReturn('USD');
    }

    public function testGetCurrencyList()
    {
        $defaultCurrencyConfigProvider = new DefaultCurrencyConfigProvider($this->configManager);

        $this->assertCount(1, $defaultCurrencyConfigProvider->getCurrencyList());
    }

    public function testGetCurrencies()
    {
        $defaultCurrencyConfigProvider = new DefaultCurrencyConfigProvider($this->configManager);

        $this->assertEquals(['USD' => 'USD'], $defaultCurrencyConfigProvider->getCurrencies());
    }
}
