<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Formatter;

use Oro\Bundle\CurrencyBundle\Formatter\NumberFormatter;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class NumberFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeSettings;

    /**
     * @var ViewTypeProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $viewTypeProvider;

    /**
     * @var NumberFormatter
     */
    private $formatter;

    protected function setUp()
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->viewTypeProvider = $this->createMock(ViewTypeProviderInterface::class);
        $this->formatter = new NumberFormatter($this->localeSettings, $this->viewTypeProvider);
    }

    /**
     * @dataProvider formatCurrencyDataProvider
     *
     * @param string $expected
     * @param string $value
     * @param string $currency
     * @param string $locale
     */
    public function testFormatCurrency(string $expected, string $value, string $currency, $locale): void
    {
        $this->localeSettings
            ->expects(self::any())
            ->method('getCurrencySymbolByCurrency')
            ->willReturnMap([['USD', 'USD'], ['EUR', 'EUR']]);

        $this->viewTypeProvider
            ->expects(self::once())
            ->method('getViewType')
            ->willReturn(ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE);

        $result = $this->formatter->formatCurrency($value, $currency, [], [], [], $locale);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formatCurrencyDataProvider(): array
    {
        return [
            'en_USD' =>  [
                'expected' => 'USD 1,234.57',
                'value' => 1234.56789,
                'currency' => 'USD',
                'locale' => 'en',
            ],
            'en_US_USD' =>  [
                'expected' => 'USD 1,234.57',
                'value' => 1234.56789,
                'currency' => 'USD',
                'locale' => 'en_US',
            ],
            'en_DE_EUR' =>  [
                'expected' => 'EUR 1,234.57',
                'value' => 1234.56789,
                'currency' => 'EUR',
                'locale' => 'en_DU',
            ],
            'de_EUR' =>  [
                'expected' => '1.234,57 EUR',
                'value' => 1234.56789,
                'currency' => 'EUR',
                'locale' => 'de',
            ],
            'de_DE_EUR' =>  [
                'expected' => '1.234,57 EUR',
                'value' => 1234.56789,
                'currency' => 'EUR',
                'locale' => 'de_DE',
            ],
            'de_US_USD' =>  [
                'expected' => '1.234,57 USD',
                'value' => 1234.56789,
                'currency' => 'USD',
                'locale' => 'de_US',
            ],
            'az_USD' => [
                'expected' => 'USD 1.234,57',
                'value' => 1234.56789,
                'currency' => 'USD',
                'locale' => 'az',
            ],
        ];
    }
}
