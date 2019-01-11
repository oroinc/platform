<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use NumberFormatter as IntlNumberFormatter;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\CalendarFactoryInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\Yaml\Yaml;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NumberFormatterTest extends TestCase
{
    /**
     * @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $localeSettings;

    /**
     * @var NumberFormatter
     */
    protected $formatter;

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this);

        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->formatter = new NumberFormatter($this->localeSettings);
    }

    /**
     * @dataProvider formatDataProvider
     *
     * @param $expected
     * @param $value
     * @param $style
     * @param $attributes
     * @param $textAttributes
     * @param $symbols
     * @param $locale
     * @param null $defaultLocale
     */
    public function testFormat(
        $expected,
        $value,
        $style,
        $attributes,
        $textAttributes,
        $symbols,
        $locale,
        $defaultLocale = null
    ) {
        if ($defaultLocale) {
            $this->localeSettings->expects($this->once())->method('getLocale')
                ->will($this->returnValue($defaultLocale));
        }
        $this->assertEquals(
            $expected,
            $this->formatter->format($value, $style, $attributes, $textAttributes, $symbols, $locale)
        );
    }

    /**
     * @return array
     */
    public function formatDataProvider()
    {
        return [
            [
                'expected' => '1,234.568',
                'value' => 1234.56789,
                'style' => \NumberFormatter::DECIMAL,
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => 'en_US'
            ],
            [
                'expected' => '1,234.568',
                'value' => 1234.56789,
                'style' => 'DECIMAL',
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => 'en_US'
            ],
            [
                'expected' => '1,234.57',
                'value' => 1234.56789,
                'style' => \NumberFormatter::DECIMAL,
                'attributes' => [
                    'fraction_digits' => 2
                ],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => null,
                'settingsLocale' => 'en_US'
            ],
            [
                'expected' => 'MINUS 10.0000,123',
                'value' => -100000.123,
                'style' => \NumberFormatter::DECIMAL,
                'attributes' => [
                    \NumberFormatter::GROUPING_SIZE => 4,
                ],
                'textAttributes' => [
                    \NumberFormatter::NEGATIVE_PREFIX => 'MINUS ',
                ],
                'symbols' => [
                    \NumberFormatter::DECIMAL_SEPARATOR_SYMBOL => ',',
                    \NumberFormatter::GROUPING_SEPARATOR_SYMBOL => '.',
                ],
                'locale' => 'en_US'
            ],
        ];
    }

    public function testFormatWithoutLocale()
    {
        $locale = 'fr_FR';
        $this->localeSettings->expects($this->once())->method('getLocale')->will($this->returnValue($locale));
        $this->assertEquals(
            '123 456,4',
            $this->formatter->format(123456.4, \NumberFormatter::DECIMAL)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage NumberFormatter has no constant 'UNKNOWN_ATTRIBUTE'
     */
    public function testFormatFails()
    {
        $this->formatter->format(
            '123',
            \NumberFormatter::DECIMAL,
            ['unknown_attribute' => 1],
            [],
            [],
            'en_US'
        );
    }

    /**
     * @dataProvider formatDecimalDataProvider
     *
     * @param $expected
     * @param $value
     * @param $attributes
     * @param $textAttributes
     * @param $symbols
     * @param $locale
     */
    public function testFormatDecimal($expected, $value, $attributes, $textAttributes, $symbols, $locale)
    {
        $this->assertEquals(
            $expected,
            $this->formatter->formatDecimal($value, $attributes, $textAttributes, $symbols, $locale)
        );
    }

    /**
     * @return array
     */
    public function formatDecimalDataProvider()
    {
        return [
            [
                'expected' => '1,234.568',
                'value' => 1234.56789,
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => 'en_US'
            ],
            [
                'expected' => '+12 345,6789000000',
                'value' => 12345.6789,
                'attributes' => [
                    'fraction_digits' => 10
                ],
                'textAttributes' => [
                    'positive_prefix' => '+',
                ],
                'symbols' => [
                    \NumberFormatter::DECIMAL_SEPARATOR_SYMBOL => ',',
                    \NumberFormatter::GROUPING_SEPARATOR_SYMBOL => ' ',
                ],
                'locale' => 'en_US'
            ],
        ];
    }

    public function testDefaultFormatCurrency()
    {
        $locale = 'en_GB';
        $currency = 'GBP';
        $currencySymbol = '£';

        $this->localeSettings->expects($this->any())->method('getLocale')->will($this->returnValue($locale));
        $this->localeSettings->expects($this->any())->method('getCurrency')->will($this->returnValue($currency));
        $this->localeSettings->expects($this->any())
            ->method('getCurrencySymbolByCurrency')
            ->with($currency)
            ->will($this->returnValue($currencySymbol));

        $this->assertEquals('£1,234.57', $this->formatter->formatCurrency(1234.56789));
    }

    /**
     * @dataProvider formatCurrencyDataProvider
     *
     * @param string $expected
     * @param int|float $value
     * @param string $currency
     * @param string $locale
     */
    public function testFormatCurrency(string $expected, $value, string $currency, string $locale)
    {
        $configManager = $this->createMock(ConfigManager::class);
        $calendarFactory = $this->createMock(CalendarFactoryInterface::class);
        $localizationManager = $this->createMock(LocalizationManager::class);

        $formatter = new NumberFormatter(new LocaleSettings($configManager, $calendarFactory, $localizationManager));
        $this->assertEquals(
            $expected,
            $formatter->formatCurrency($value, $currency, [], [], [], $locale)
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formatCurrencyDataProvider(): array
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/Data/format_currency_data.yml'));
    }

    /**
     * @dataProvider formatPercentDataProvider
     *
     * @param $expected
     * @param $value
     * @param $attributes
     * @param $textAttributes
     * @param $symbols
     * @param $locale
     */
    public function testFormatPercent($expected, $value, $attributes, $textAttributes, $symbols, $locale)
    {
        $this->assertEquals(
            $expected,
            $this->formatter->formatPercent($value, $attributes, $textAttributes, $symbols, $locale)
        );
    }

    /**
     * @return array
     */
    public function formatPercentDataProvider()
    {
        return [
            [
                'expected' => '123,456.789%',
                'value' => 1234.56789,
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => 'en_US'
            ],
        ];
    }

    /**
     * @dataProvider formatSpelloutDataProvider
     *
     * @param $expected
     * @param $value
     * @param $attributes
     * @param $textAttributes
     * @param $symbols
     * @param $locale
     */
    public function testFormatSpellout($expected, $value, $attributes, $textAttributes, $symbols, $locale)
    {
        $this->assertEquals(
            $expected,
            $this->formatter->formatSpellout($value, $attributes, $textAttributes, $symbols, $locale)
        );
    }

    /**
     * @return array
     */
    public function formatSpelloutDataProvider()
    {
        return [
            [
                'expected' => 'twenty-one',
                'value' => 21,
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => 'en_US'
            ],
        ];
    }

    /**
     * @dataProvider formatDurationDataProvider
     *
     * @param $expected
     * @param $value
     * @param $attributes
     * @param $textAttributes
     * @param $symbols
     * @param $locale
     * @param $default
     */
    public function testFormatDuration($expected, $value, $attributes, $textAttributes, $symbols, $locale, $default)
    {
        $this->assertEquals(
            $expected,
            $this->formatter->formatDuration($value, $attributes, $textAttributes, $symbols, $locale, $default)
        );
    }

    /**
     * @return array
     */
    public function formatDurationDataProvider()
    {
        return [
            'default' => [
                'expected' => '1:01:01',
                'value' => 3661,
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => 'en',
                'default' => false
            ],
            'with default format' => [
                'expected' => '00:00:01',
                'value' => 1,
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => null,
                'default' => true
            ],
            'without default format' => [
                'expected' => '1 sec.',
                'value' => 1,
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => 'en',
                'default' => false
            ],
            'with_words' => [
                'expected' => '1 hour, 1 minute, 1 second',
                'value' => 3661,
                'attributes' => [],
                'textAttributes' => [
                    \NumberFormatter::DEFAULT_RULESET => "%with-words"
                ],
                'symbols' => [],
                'locale' => 'en_US',
                'default' => false
            ],
            'fix_for_localization_problems' => [
                'expected' => '01:01:01',
                'value' => 3661,
                'attributes' => [],
                'textAttributes' => [],
                'symbols' => [],
                'locale' => 'ru',
                'default' => false
            ],
        ];
    }

    public function testFormatOrdinal()
    {
        $result = $this->formatter->formatOrdinal(1, [], [], [], 'en_US');

        // expected result is: 1st but in som versions of ICU 1ˢᵗ is also possible
        $this->assertStringStartsWith('1', $result);
        $this->assertNotEquals('1', $result);
    }

    /**
     * @dataProvider getAttributeDataProvider
     *
     * @param $attribute
     * @param $style
     * @param $locale
     * @param $expected
     * @param $attributes
     */
    public function testGetAttribute($attribute, $style, $locale, $expected, $attributes)
    {
        $this->assertSame(
            $expected,
            $this->formatter->getAttribute(
                $attribute,
                $style,
                $locale,
                $attributes
            )
        );
    }

    /**
     * @return array
     */
    public function getAttributeDataProvider()
    {
        $intlFormatter = new IntlNumberFormatter('en_US', \NumberFormatter::DECIMAL);
        $maxIntegerDigits = $intlFormatter->getAttribute(\NumberFormatter::MAX_INTEGER_DIGITS);

        return [
            ['parse_int_only', 'DECIMAL', 'en_US', 0, []],
            ['parse_int_only', null, 'en_US', 0, []],
            ['GROUPING_USED', 'decimal', 'en_US', 1, []],
            [\NumberFormatter::DECIMAL_ALWAYS_SHOWN, \NumberFormatter::DECIMAL, 'en_US', 0, []],
            [\NumberFormatter::MAX_INTEGER_DIGITS, \NumberFormatter::DECIMAL, 'en_US', $maxIntegerDigits, []],
            [\NumberFormatter::MIN_INTEGER_DIGITS, \NumberFormatter::DECIMAL, 'en_US', 1, []],
            [\NumberFormatter::INTEGER_DIGITS, \NumberFormatter::DECIMAL, 'en_US', 1, []],
            [\NumberFormatter::MAX_FRACTION_DIGITS, \NumberFormatter::DECIMAL, 'en_US', 3, []],
            [\NumberFormatter::MIN_FRACTION_DIGITS, \NumberFormatter::DECIMAL, 'en_US', 0, []],
            [\NumberFormatter::MAX_FRACTION_DIGITS, \NumberFormatter::CURRENCY, 'en_US', 2, []],
            [\NumberFormatter::MIN_FRACTION_DIGITS, \NumberFormatter::CURRENCY, 'en_US', 2, []],
            [\NumberFormatter::FRACTION_DIGITS, \NumberFormatter::DECIMAL, 'en_US', 0, []],
            [\NumberFormatter::MULTIPLIER, \NumberFormatter::DECIMAL, 'en_US', 1, []],
            [\NumberFormatter::GROUPING_SIZE, \NumberFormatter::DECIMAL, 'en_US', 3, []],
            [\NumberFormatter::ROUNDING_MODE, \NumberFormatter::DECIMAL, 'en_US', 4, []],
            [\NumberFormatter::ROUNDING_INCREMENT, \NumberFormatter::DECIMAL, 'en_US', 0.0, []],
            [\NumberFormatter::FORMAT_WIDTH, \NumberFormatter::DECIMAL, 'en_US', 0, []],
            [\NumberFormatter::PADDING_POSITION, \NumberFormatter::DECIMAL, 'en_US', 0, []],
            [\NumberFormatter::SECONDARY_GROUPING_SIZE, \NumberFormatter::DECIMAL, 'en_US', 0, []],
            [\NumberFormatter::SIGNIFICANT_DIGITS_USED, \NumberFormatter::DECIMAL, 'en_US', 0, []],
            [\NumberFormatter::MIN_SIGNIFICANT_DIGITS, \NumberFormatter::DECIMAL, 'en_US', 1, []],
            [\NumberFormatter::MAX_SIGNIFICANT_DIGITS, \NumberFormatter::DECIMAL, 'en_US', 6, []],
            [\NumberFormatter::MAX_FRACTION_DIGITS, \NumberFormatter::PERCENT, 'en_US', 0, [
                \NumberFormatter::MAX_FRACTION_DIGITS => 4,
            ]],
            [\NumberFormatter::MAX_FRACTION_DIGITS, \NumberFormatter::PERCENT, 'en_US', 4, [
                \NumberFormatter::FRACTION_DIGITS => 4,
                \NumberFormatter::MIN_FRACTION_DIGITS => 0,
                \NumberFormatter::MAX_FRACTION_DIGITS => 4,
            ]],
        ];
    }

    /**
     * @dataProvider getTextAttributeDataProvider
     *
     * @param $attribute
     * @param $locale
     * @param $style
     * @param $expected
     */
    public function testTextAttribute($attribute, $locale, $style, $expected)
    {
        $this->assertSame(
            $expected,
            $this->formatter->getTextAttribute(
                $attribute,
                $locale,
                $style
            )
        );
    }

    /**
     * @return array
     */
    public function getTextAttributeDataProvider()
    {
        return [
            ['POSITIVE_PREFIX', 'DECIMAL', 'en_US', ''],
            ['negative_prefix', 'decimal', 'en_US', '-'],
            [\NumberFormatter::NEGATIVE_SUFFIX, \NumberFormatter::DECIMAL, 'en_US', ''],
            [\NumberFormatter::CURRENCY_CODE, \NumberFormatter::CURRENCY, 'en_US', 'USD'],
            [\NumberFormatter::DEFAULT_RULESET, \NumberFormatter::DECIMAL, 'en_US', false],
            [\NumberFormatter::PUBLIC_RULESETS, \NumberFormatter::DECIMAL, 'en_US', false]
        ];
    }

    /**
     * @dataProvider getSymbolDataProvider
     *
     * @param $symbol
     * @param $locale
     * @param $style
     * @param $expected
     */
    public function testGetNumberFormatterSymbol($symbol, $locale, $style, $expected)
    {
        $this->assertSame(
            $expected,
            $this->formatter->getSymbol(
                $symbol,
                $locale,
                $style
            )
        );
    }

    /**
     * @return array
     */
    public function getSymbolDataProvider()
    {
        return [
            ['DECIMAL_SEPARATOR_SYMBOL', 'DECIMAL', 'en_US', '.'],
            [\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', ','],
            ['pattern_separator_symbol', 'decimal', 'en_US', ';'],
            [\NumberFormatter::PERCENT_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '%'],
            [\NumberFormatter::ZERO_DIGIT_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '0'],
            [\NumberFormatter::DIGIT_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '#'],
            [\NumberFormatter::MINUS_SIGN_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '-'],
            [\NumberFormatter::PLUS_SIGN_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '+'],
            [\NumberFormatter::CURRENCY_SYMBOL, \NumberFormatter::CURRENCY, 'en_US', '$'],
            [\NumberFormatter::INTL_CURRENCY_SYMBOL, \NumberFormatter::CURRENCY, 'en_US', 'USD'],
            [\NumberFormatter::MONETARY_SEPARATOR_SYMBOL, \NumberFormatter::CURRENCY, 'en_US', '.'],
            [\NumberFormatter::EXPONENTIAL_SYMBOL, \NumberFormatter::SCIENTIFIC, 'en_US', 'E'],
            [\NumberFormatter::PERMILL_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '‰'],
            [\NumberFormatter::PAD_ESCAPE_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '*'],
            [\NumberFormatter::INFINITY_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '∞'],
            [\NumberFormatter::NAN_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', 'NaN'],
            [\NumberFormatter::SIGNIFICANT_DIGIT_SYMBOL, \NumberFormatter::DECIMAL, 'en_US', '@'],
            [\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, \NumberFormatter::CURRENCY, 'en_US', ','],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage NumberFormatter style '19' is invalid
     */
    public function testFormatWithInvalidStyle()
    {
        $this->formatter->format(123, \NumberFormatter::LENIENT_PARSE);
    }

    /**
     * @param bool $expected
     * @param string $currency
     * @param string|null $locale
     * @param string|null $defaultLocale
     * @dataProvider isCurrencySymbolPrependDataProvider
     */
    public function testIsCurrencySymbolPrepend($expected, $currency, $locale, $defaultLocale = null)
    {
        if ($defaultLocale) {
            $this->localeSettings->expects($this->once())
                ->method('getLocale')
                ->will($this->returnValue($defaultLocale));
        } else {
            $this->localeSettings->expects($this->never())
                ->method('getLocale');
        }

        $this->assertEquals($expected, $this->formatter->isCurrencySymbolPrepend($currency, $locale));
    }

    public function testIsCurrencySymbolPrependWithoutLocale()
    {
        $this->localeSettings
            ->expects($this->once())
            ->method('getLocale')
            ->will($this->returnValue('en'));

        $this->localeSettings
            ->expects($this->once())
            ->method('getCurrency')
            ->will($this->returnValue('RUR'));

        $this->assertEquals(true, $this->formatter->isCurrencySymbolPrepend());
    }

    /**
     * @return array
     */
    public function isCurrencySymbolPrependDataProvider()
    {
        return [
            'default locale' => [
                'expected' => true,
                'currency' => 'USD',
                'locale' => null,
                'defaultLocale' => 'en',
            ],
            'custom locale' => [
                'expected' => false,
                'currency' => 'RUR',
                'locale' => 'ru',
            ],
        ];
    }
}
