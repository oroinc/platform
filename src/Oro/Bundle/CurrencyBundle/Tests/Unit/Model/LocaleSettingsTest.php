<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\LocaleBundle\Model\CalendarFactoryInterface;

class LocaleSettingsTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CalendarFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarFactory;

    /** @var ViewTypeProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $viewTypeProvider;

    /** @var CurrencyProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyProvider;

    /** @var LocaleSettings */
    private $localeSettings;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->calendarFactory = $this->createMock(CalendarFactoryInterface::class);
        $this->viewTypeProvider = $this->createMock(ViewTypeProviderInterface::class);
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $this->localeSettings = new LocaleSettings(
            $this->configManager,
            $this->calendarFactory,
            $this->viewTypeProvider,
            $this->currencyProvider
        );
    }

    /**
     * @dataProvider getCurrencySymbolByCurrencyDataProvider
     *
     * @param string $viewType
     * @param array $currencyList
     * @param string $currencyCode
     * @param string $expectedSymbol
     */
    public function testGetCurrencySymbolByCurrency(
        string $viewType,
        array $currencyList,
        string $currencyCode,
        string $expectedSymbol
    ) {
        $this->viewTypeProvider
            ->expects(self::once())
            ->method('getViewType')
            ->willReturn($viewType);

        $this->currencyProvider
            ->expects(self::any())
            ->method('getCurrencyList')
            ->willReturn($currencyList);

        self::assertEquals($expectedSymbol, $this->localeSettings->getCurrencySymbolByCurrency($currencyCode));
    }

    /**
     * @return array
     */
    public function getCurrencySymbolByCurrencyDataProvider(): array
    {
        return [
            'symbol view type, enabled currency' => [
                'viewType' => ViewTypeProviderInterface::VIEW_TYPE_SYMBOL,
                'currencyList' => ['USD'],
                'currencyCode' => 'USD',
                'expectedSymbol' => '$',
            ],
            'iso code view type, enabled currency' => [
                'viewType' => ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE,
                'currencyList' => ['USD'],
                'currencyCode' => 'USD',
                'expectedSymbol' => 'USD',
            ],
            'symbol code view type, disabled currency' => [
                'viewType' => ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE,
                'currencyList' => ['EUR'],
                'currencyCode' => 'USD',
                'expectedSymbol' => 'USD',
            ],
            'iso code view type, disabled currency' => [
                'viewType' => ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE,
                'currencyList' => ['EUR'],
                'currencyCode' => 'USD',
                'expectedSymbol' => 'USD',
            ],
        ];
    }
}
