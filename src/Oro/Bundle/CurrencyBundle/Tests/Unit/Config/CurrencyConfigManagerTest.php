<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager;

class CurrencyConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var int
     */
    protected $allAvailableCurrenciesCount;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
                                    ->disableOriginalConstructor()
                                    ->setMethods(['get'])
                                    ->getMock();

        $this->configManager->method('get')
                            ->with('oro_currency.default_currency')
                            ->willReturn('USD');
    }


    public function testGetCurrencyList()
    {
        $expectedCount = 1;
        $currencyConfigManager = new CurrencyConfigManager($this->configManager);

        $this->assertCount($expectedCount, $currencyConfigManager->getCurrencyList());
    }
}
