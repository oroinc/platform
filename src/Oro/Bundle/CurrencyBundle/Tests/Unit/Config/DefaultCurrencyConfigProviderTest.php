<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Config\DefaultCurrencyConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultCurrencyConfigProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.default_currency')
            ->willReturn('USD');
    }

    public function testGetCurrencyList(): void
    {
        $defaultCurrencyConfigProvider = new DefaultCurrencyConfigProvider($this->configManager);

        $this->assertCount(1, $defaultCurrencyConfigProvider->getCurrencyList());
    }

    public function testGetCurrencies(): void
    {
        $defaultCurrencyConfigProvider = new DefaultCurrencyConfigProvider($this->configManager);

        $this->assertEquals(['USD' => 'USD'], $defaultCurrencyConfigProvider->getCurrencies());
    }
}
