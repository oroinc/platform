<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Brick\Math\BigDecimal;
use NumberFormatter as IntlNumberFormatter;
use Oro\Bundle\LocaleBundle\Formatter\Factory\IntlNumberFormatterFactory;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

/**
 * Please note that this test should check only our logic,
 * DO NOT use IntlNumberFormatter directly to prevent problems with different libicu versions
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NumberFormatterTest extends \PHPUnit\Framework\TestCase
{
    private const UNFORMATTED_VALUE = '18908997.16908';
    private const FORMATTED_VALUE = 18908997.16908;
    private const LOCALE = 'en_US';
    private const CURRENCY = 'USD';

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var IntlNumberFormatterFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $intlNumberFormatterFactory;

    /** @var NumberFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->intlNumberFormatterFactory = $this->createMock(IntlNumberFormatterFactory::class);

        $this->formatter = new NumberFormatter($this->localeSettings, $this->intlNumberFormatterFactory);
    }

    public function testFormat(): void
    {
        $style = IntlNumberFormatter::DECIMAL;

        $this->mockFormat($style);

        $this->assertEquals(
            self::FORMATTED_VALUE,
            $this->formatter->format(self::UNFORMATTED_VALUE, $style, [], [], [], self::LOCALE)
        );

        // check local cache
        $this->assertEquals(
            self::FORMATTED_VALUE,
            $this->formatter->format(self::UNFORMATTED_VALUE, $style, [], [], [], self::LOCALE)
        );
    }

    /**
     * @param string $style
     */
    private function mockFormat(string $style): void
    {
        $intlNumberFormatter = $this->createMock(IntlNumberFormatter::class);
        $this->intlNumberFormatterFactory
            ->expects($this->once())
            ->method('create')
            ->with(self::LOCALE, $style, [], [], [])
            ->willReturn($intlNumberFormatter);

        $intlNumberFormatter
            ->expects($this->atLeastOnce())
            ->method('format')
            ->with(self::UNFORMATTED_VALUE)
            ->willReturn(self::FORMATTED_VALUE);
    }

    public function testFormatCurrency(): void
    {
        $expectedLocale = 'en_US@currency=USD';
        $value = '42';
        $formattedValue = '42$';

        $intlNumberFormatter = $this->createMock(IntlNumberFormatter::class);
        $this->intlNumberFormatterFactory
            ->expects($this->once())
            ->method('create')
            ->with($expectedLocale, IntlNumberFormatter::CURRENCY, [], [], [])
            ->willReturn($intlNumberFormatter);

        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('formatCurrency')
            ->with($value, self::CURRENCY)
            ->willReturn($formattedValue);

        $symbol = '$';
        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('getSymbol')
            ->with(IntlNumberFormatter::CURRENCY_SYMBOL)
            ->willReturn($symbol);

        $this->localeSettings
            ->expects($this->once())
            ->method('getCurrencySymbolByCurrency')
            ->with(self::CURRENCY, self::LOCALE)
            ->willReturn($symbol);

        $this->assertEquals(
            $formattedValue,
            $this->formatter->formatCurrency($value, self::CURRENCY, [], [], [], self::LOCALE)
        );

        // check local cache
        $this->assertEquals(
            $formattedValue,
            $this->formatter->formatCurrency($value, self::CURRENCY, [], [], [], self::LOCALE)
        );
    }

    /**
     * @dataProvider formatCurrencyWhenAnotherSymbolDataProvider
     *
     * @param string $formattedValue
     * @param string $expectedValue
     */
    public function testFormatCurrencyWhenAnotherSymbol(string $formattedValue, string $expectedValue): void
    {
        $expectedLocale = 'en_US@currency=USD';
        $value = '42';

        $intlNumberFormatter = $this->createMock(IntlNumberFormatter::class);
        $this->intlNumberFormatterFactory
            ->expects($this->once())
            ->method('create')
            ->with($expectedLocale, IntlNumberFormatter::CURRENCY, [], [], [])
            ->willReturn($intlNumberFormatter);

        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('formatCurrency')
            ->with($value, self::CURRENCY)
            ->willReturn($formattedValue);

        $fromSymbol = '$';
        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('getSymbol')
            ->with(IntlNumberFormatter::CURRENCY_SYMBOL)
            ->willReturn($fromSymbol);

        $toSymbol = 'USD';
        $this->localeSettings
            ->expects($this->once())
            ->method('getCurrencySymbolByCurrency')
            ->with(self::CURRENCY, self::LOCALE)
            ->willReturn($toSymbol);

        $this->assertEquals(
            $expectedValue,
            $this->formatter->formatCurrency($value, self::CURRENCY, [], [], [], self::LOCALE)
        );

        // check local cache
        $this->assertEquals(
            $expectedValue,
            $this->formatter->formatCurrency($value, self::CURRENCY, [], [], [], self::LOCALE)
        );
    }

    /**
     * @return array
     */
    public function formatCurrencyWhenAnotherSymbolDataProvider(): array
    {
        return [
            [
                'formattedValue' => '42$',
                'expectedValue' => '42USD',
            ],
            [
                'formattedValue' => '42 $',
                'expectedValue' => '42 USD',
            ],
            [
                'formattedValue' => '42  $',
                'expectedValue' => '42 USD',
            ],
            [
                'formattedValue' => '$42',
                'expectedValue' => 'USD 42',
            ],
        ];
    }

    public function testFormatCurrencyWhenNoCurrencyCode(): void
    {
        $expectedLocale = 'en_US@currency=USD';
        $value = '42';
        $formattedValue = '42$';

        $this->localeSettings
            ->expects($this->exactly(2))
            ->method('getCurrency')
            ->willReturn(self::CURRENCY);

        $intlNumberFormatter = $this->createMock(IntlNumberFormatter::class);
        $this->intlNumberFormatterFactory
            ->expects($this->once())
            ->method('create')
            ->with($expectedLocale, IntlNumberFormatter::CURRENCY, [], [], [])
            ->willReturn($intlNumberFormatter);

        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('formatCurrency')
            ->with($value, self::CURRENCY)
            ->willReturn($formattedValue);

        $symbol = '$';
        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('getSymbol')
            ->with(IntlNumberFormatter::CURRENCY_SYMBOL)
            ->willReturn($symbol);

        $this->localeSettings
            ->expects($this->once())
            ->method('getCurrencySymbolByCurrency')
            ->with(self::CURRENCY, self::LOCALE)
            ->willReturn($symbol);

        $this->assertEquals(
            $formattedValue,
            $this->formatter->formatCurrency($value, null, [], [], [], self::LOCALE)
        );

        // check local cache
        $this->assertEquals(
            $formattedValue,
            $this->formatter->formatCurrency($value, null, [], [], [], self::LOCALE)
        );
    }

    public function testFormatCurrencyWhenNoLocale(): void
    {
        $expectedLocale = 'en_US@currency=USD';
        $value = '42';
        $formattedValue = '42$';

        $this->localeSettings
            ->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        $intlNumberFormatter = $this->createMock(IntlNumberFormatter::class);
        $this->intlNumberFormatterFactory
            ->expects($this->once())
            ->method('create')
            ->with($expectedLocale, IntlNumberFormatter::CURRENCY, [], [], [])
            ->willReturn($intlNumberFormatter);

        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('formatCurrency')
            ->with($value, self::CURRENCY)
            ->willReturn($formattedValue);

        $symbol = '$';
        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('getSymbol')
            ->with(IntlNumberFormatter::CURRENCY_SYMBOL)
            ->willReturn($symbol);

        $this->localeSettings
            ->expects($this->once())
            ->method('getCurrencySymbolByCurrency')
            ->with(self::CURRENCY, self::LOCALE)
            ->willReturn($symbol);

        $this->assertEquals(
            $formattedValue,
            $this->formatter->formatCurrency($value, self::CURRENCY, [], [], [], null)
        );

        // check local cache
        $this->assertEquals(
            $formattedValue,
            $this->formatter->formatCurrency($value, self::CURRENCY, [], [], [], null)
        );
    }

    /**
     * @param string $locale
     * @param string $currencyCode
     * @param string $currencySymbol
     * @param float $value
     * @param string $formattedValue
     * @param array $attributes
     *
     * @dataProvider dataProviderFormatCurrency
     */
    public function testFormatCurrencyWithNoneFractionDigits(
        string $locale,
        string $currencyCode,
        string $currencySymbol,
        float $value,
        string $formattedValue,
        array $attributes
    ): void {
        $this->localeSettings
            ->expects($this->any())
            ->method('getCurrencySymbolByCurrency')
            ->with($currencyCode, $locale)
            ->willReturn($currencySymbol);

        $intlNumberFormatter = $this->createMock(IntlNumberFormatter::class);
        $intlNumberFormatter
            ->expects($this->once())
            ->method('formatCurrency')
            ->with($value, $currencyCode)
            ->willReturn($formattedValue);

        $this->intlNumberFormatterFactory
            ->expects($this->once())
            ->method('create')
            ->with($locale. '@currency=' . $currencyCode, IntlNumberFormatter::CURRENCY, $attributes, [], [])
            ->willReturn($intlNumberFormatter);

        $currency = $this->formatter->formatCurrency($value, $currencyCode, $attributes, [], [], $locale);
        $this->assertEquals($formattedValue, $currency);
    }

    /**
     * @return array[]
     */
    public function dataProviderFormatCurrency(): array
    {
        return [
            'Andorran Peseta(ADP) without decimal part' => [
                'locale' => 'en_US',
                'currencyCode' => 'ADP',
                'currencySymbol' => '',
                'value' => 1.0,
                'formattedValue' => 'ADP 1',
                'attributes' => [],
            ],
            'Andorran Peseta(ADP) with decimal part and fixed fraction digits' => [
                'locale' => 'fr_FR',
                'currencyCode' => 'ADP',
                'currencySymbol' => '',
                'value' => 9.9999,
                'formattedValue' => '10,000 ADP',
                'attributes' => [IntlNumberFormatter::MIN_FRACTION_DIGITS => 3],
            ],
        ];
    }


    /**
     * @param string $locale
     * @param string $currencyCode
     * @param string $currencySymbol
     * @param float $value
     * @param string $formattedValue
     * @param array $attributes
     * @param int $minFractionDigits
     *
     * @dataProvider dataProviderFormatCurrencyWithFractionDigits
     */
    public function testFormatCurrencyWithFractionDigits(
        string $locale,
        string $currencyCode,
        string $currencySymbol,
        float $value,
        string $formattedValue,
        array $attributes,
        int $minFractionDigits = 0
    ): void {
        $this->localeSettings
            ->expects($this->any())
            ->method('getCurrencySymbolByCurrency')
            ->with($currencyCode, $locale)
            ->willReturn($currencySymbol);

        $intlNumberFormatter = $this->createMock(IntlNumberFormatter::class);
        $intlNumberFormatter
            ->expects($this->once())
            ->method('formatCurrency')
            ->with($value, $currencyCode)
            ->willReturn($formattedValue);
        $intlNumberFormatter
            ->expects($this->once())
            ->method('getAttribute')
            ->with(\NumberFormatter::MIN_FRACTION_DIGITS)
            ->willReturn($minFractionDigits);
        $intlNumberFormatter
            ->expects($this->exactly(2))
            ->method('setAttribute')
            ->withConsecutive(
                [IntlNumberFormatter::MIN_FRACTION_DIGITS, BigDecimal::of($value)->getScale()],
                [IntlNumberFormatter::MIN_FRACTION_DIGITS, $minFractionDigits]
            );

        $this->intlNumberFormatterFactory
            ->expects($this->once())
            ->method('create')
            ->with($locale. '@currency=' . $currencyCode, IntlNumberFormatter::CURRENCY, $attributes, [], [])
            ->willReturn($intlNumberFormatter);

        $currency = $this->formatter->formatCurrency($value, $currencyCode, $attributes, [], [], $locale);
        $this->assertEquals($formattedValue, $currency);
    }

    public function dataProviderFormatCurrencyWithFractionDigits(): array
    {
        return [
            'Andorran Peseta(ADP) without scientific notation value' => [
                'locale' => 'en_US',
                'currencyCode' => 'ADP',
                'currencySymbol' => '',
                'value' => 2.3456e2,
                'formattedValue' => 'ADP 234.56',
                'attributes' => [],
            ],
            'Andorran Peseta(ADP) with decimal part' => [
                'locale' => 'en_US',
                'currencyCode' => 'ADP',
                'currencySymbol' => '',
                'value' => 9.9999,
                'formattedValue' => 'ADP 9.9999',
                'attributes' => [],
            ],
            'US Dollar with decimal part' => [
                'locale' => 'en_US',
                'currencyCode' => 'USD',
                'currencySymbol' => '',
                'value' => 9.9999,
                'formattedValue' => '$9.9999',
                'attributes' => [],
                'minFractionDigits' => 2
            ]
        ];
    }


    public function testFormatDecimal(): void
    {
        $this->mockFormat(IntlNumberFormatter::DECIMAL);

        $this->assertEquals(
            self::FORMATTED_VALUE,
            $this->formatter->formatDecimal(self::UNFORMATTED_VALUE, [], [], [], self::LOCALE)
        );
    }

    public function testFormatPercent(): void
    {
        $this->mockFormat(IntlNumberFormatter::PERCENT);

        $this->assertEquals(
            self::FORMATTED_VALUE,
            $this->formatter->formatPercent(self::UNFORMATTED_VALUE, [], [], [], self::LOCALE)
        );
    }

    public function testFormatSpellout(): void
    {
        $this->mockFormat(IntlNumberFormatter::SPELLOUT);

        $this->assertEquals(
            self::FORMATTED_VALUE,
            $this->formatter->formatSpellout(self::UNFORMATTED_VALUE, [], [], [], self::LOCALE)
        );
    }

    /**
     * @dataProvider formatDurationWhenDefaultFormatDataProvider
     *
     * @param string|int|float|\DateTime $value
     * @param string $formattedValue
     */
    public function testFormatDurationWhenDefaultFormat($value, string $formattedValue): void
    {
        $this->intlNumberFormatterFactory
            ->expects($this->never())
            ->method('create');

        $this->assertEquals(
            $formattedValue,
            $this->formatter->formatDuration($value, [], [], [], self::LOCALE, true)
        );
    }

    /**
     * @return array
     */
    public function formatDurationWhenDefaultFormatDataProvider(): array
    {
        return [
            [
                'value' => '',
                'formattedValue' => '00:00:00',
            ],
            [
                'value' => 0,
                'formattedValue' => '00:00:00',
            ],
            [
                'value' => -42,
                'formattedValue' => '00:00:42',
            ],
            [
                'value' => 42.1,
                'formattedValue' => '00:00:42',
            ],
            [
                'value' => '42',
                'formattedValue' => '00:00:42',
            ],
            [
                'value' => 42,
                'formattedValue' => '00:00:42',
            ],
            [
                'value' => 142,
                'formattedValue' => '00:02:22',
            ],
            [
                'value' => 4242,
                'formattedValue' => '01:10:42',
            ],
            [
                'value' => 424242,
                'formattedValue' => '117:50:42',
            ],
            [
                'value' => new \DateTime('11.09.2020 13:23:32', new \DateTimeZone('UTC')),
                'formattedValue' => '444397:23:32',
            ],
        ];
    }

    /**
     * @dataProvider formatDurationWhenNotDefaultFormatDataProvider
     *
     * @param string|int|float|\DateTime $value
     * @param string $formattedValue
     * @param string $expectedValue
     */
    public function testFormatDurationWhenNotDefaultFormat($value, string $formattedValue, string $expectedValue): void
    {
        $intlNumberFormatterDuration = $this->createMock(IntlNumberFormatter::class);
        $intlNumberFormatterDefault = $this->createMock(IntlNumberFormatter::class);
        $this->intlNumberFormatterFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap(
                [
                    [self::LOCALE, IntlNumberFormatter::DURATION, [], [], [], $intlNumberFormatterDuration],
                    ['', IntlNumberFormatter::DEFAULT_STYLE, [], [], [], $intlNumberFormatterDefault],
                ]
            );

        $intlNumberFormatterDuration
            ->expects($this->once())
            ->method('format')
            ->with(abs($value))
            ->willReturn($formattedValue);

        $intlNumberFormatterDefault
            ->expects($this->exactly(2))
            ->method('getSymbol')
            ->willReturnMap(
                [
                    [IntlNumberFormatter::GROUPING_SEPARATOR_SYMBOL, ' '],
                    [IntlNumberFormatter::DECIMAL_SEPARATOR_SYMBOL, ','],
                ]
            )
            ->willReturn($formattedValue);

        $this->assertEquals(
            $expectedValue,
            $this->formatter->formatDuration($value, [], [], [], self::LOCALE, false)
        );
    }

    /**
     * @return array
     */
    public function formatDurationWhenNotDefaultFormatDataProvider(): array
    {
        return [
            'value is int' => [
                'value' => 142,
                'formattedValue' => '2 minutes, 22 seconds',
                'expectedValue' => '2 minutes, 22 seconds',
            ],
            'value is string' => [
                'value' => '142',
                'formattedValue' => '2 minutes, 22 seconds',
                'expectedValue' => '2 minutes, 22 seconds',
            ],
            'value is negative' => [
                'value' => -142,
                'formattedValue' => '2 minutes, 22 seconds',
                'expectedValue' => '2 minutes, 22 seconds',
            ],
            'duration is not valid' => [
                'value' => '142',
                'formattedValue' => '2 22',
                'expectedValue' => '00:02:22',
            ],
        ];
    }

    /**
     * @param string $style
     * @return IntlNumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockCreate(string $style): IntlNumberFormatter
    {
        $intlNumberFormatter = $this->createMock(IntlNumberFormatter::class);
        $this->intlNumberFormatterFactory
            ->expects($this->once())
            ->method('create')
            ->with(self::LOCALE, $style, [], [], [])
            ->willReturn($intlNumberFormatter);

        return $intlNumberFormatter;
    }

    public function testFormatOrdinal(): void
    {
        $this->mockFormat(IntlNumberFormatter::ORDINAL);

        $this->assertEquals(
            self::FORMATTED_VALUE,
            $this->formatter->formatOrdinal(self::UNFORMATTED_VALUE, [], [], [], self::LOCALE)
        );
    }

    public function testGetAttribute(): void
    {
        $style = 'decimal';
        $this->mockCreate($style);

        $this->formatter->getAttribute('MAX_INTEGER_DIGITS', $style, self::LOCALE, []);
    }

    public function testGetAttributeWhenInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NumberFormatter has no constant \'INVALID\'');

        $this->formatter->getAttribute('INVALID', IntlNumberFormatter::DECIMAL, self::LOCALE, []);
    }

    public function testGetTextAttribute(): void
    {
        $style = 'decimal';
        $this->mockCreate($style);

        $this->formatter->getTextAttribute('MAX_INTEGER_DIGITS', $style, self::LOCALE);
    }

    public function testGetTextAttributeWhenInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NumberFormatter has no constant \'INVALID\'');

        $this->formatter->getTextAttribute('INVALID', IntlNumberFormatter::DECIMAL, self::LOCALE);
    }

    public function testGetSymbol(): void
    {
        $style = 'decimal';
        $this->mockCreate($style);

        $this->formatter->getSymbol('MAX_INTEGER_DIGITS', $style, self::LOCALE);
    }

    public function testGetSymbolWhenInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NumberFormatter has no constant \'INVALID\'');

        $this->formatter->getSymbol('INVALID', IntlNumberFormatter::DECIMAL, self::LOCALE);
    }

    /**
     * @dataProvider currencySymbolPrependDataProvider
     *
     * @param string $formattedValue
     * @param bool|null $expectedResult
     */
    public function testIsCurrencySymbolPrepend(string $formattedValue, ?bool $expectedResult): void
    {
        $intlNumberFormatter = $this->mockCreate(IntlNumberFormatter::CURRENCY);

        $intlNumberFormatter
            ->expects($this->once())
            ->method('formatCurrency')
            ->with(123, self::CURRENCY)
            ->willReturn($formattedValue);

        $this->assertSame($expectedResult, $this->formatter->isCurrencySymbolPrepend(self::CURRENCY, self::LOCALE));
    }

    /**
     * @return array
     */
    public function currencySymbolPrependDataProvider(): array
    {
        return [
            [
                'formattedValue' => '123$',
                'expectedResult' => false,
            ],
            [
                'formattedValue' => '123 $',
                'expectedResult' => false,
            ],
            [
                'formattedValue' => '$123',
                'expectedResult' => true,
            ],
            [
                'formattedValue' => '$ 123',
                'expectedResult' => true,
            ],
            [
                'formattedValue' => '123US$',
                'expectedResult' => false,
            ],
            [
                'formattedValue' => '123 US$',
                'expectedResult' => false,
            ],
            [
                'formattedValue' => 'US$123',
                'expectedResult' => true,
            ],
            [
                'formattedValue' => 'US$ 123',
                'expectedResult' => true,
            ],
            [
                'formattedValue' => '123USD',
                'expectedResult' => false,
            ],
            [
                'formattedValue' => '123 USD',
                'expectedResult' => false,
            ],
            [
                'formattedValue' => 'USD123',
                'expectedResult' => true,
            ],
            [
                'formattedValue' => 'USD 123',
                'expectedResult' => true,
            ],
            [
                'formattedValue' => '123',
                'expectedResult' => null,
            ],
        ];
    }

    /**
     * @dataProvider currencySymbolPrependDataProvider
     *
     * @param string $formattedValue
     * @param bool|null $expectedResult
     */
    public function testIsCurrencySymbolPrependWhenNoLocale(string $formattedValue, ?bool $expectedResult): void
    {
        $intlNumberFormatter = $this->mockCreate(IntlNumberFormatter::CURRENCY);

        $this->localeSettings
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        $intlNumberFormatter
            ->expects($this->once())
            ->method('formatCurrency')
            ->with(123, self::CURRENCY)
            ->willReturn($formattedValue);

        $this->assertSame($expectedResult, $this->formatter->isCurrencySymbolPrepend(self::CURRENCY, null));
    }

    /**
     * @dataProvider currencySymbolPrependDataProvider
     *
     * @param string $formattedValue
     * @param bool|null $expectedResult
     */
    public function testIsCurrencySymbolPrependWhenNoCurrency(string $formattedValue, ?bool $expectedResult): void
    {
        $intlNumberFormatter = $this->mockCreate(IntlNumberFormatter::CURRENCY);

        $this->localeSettings
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn(self::CURRENCY);

        $intlNumberFormatter
            ->expects($this->once())
            ->method('formatCurrency')
            ->with(123, self::CURRENCY)
            ->willReturn($formattedValue);

        $this->assertEquals($expectedResult, $this->formatter->isCurrencySymbolPrepend(null, self::LOCALE));
    }

    public function testIsCurrencySymbolPrependWhenLocalCache(): void
    {
        $intlNumberFormatter = $this->mockCreate(IntlNumberFormatter::CURRENCY);
        $intlNumberFormatter
            ->expects($this->once())
            ->method('formatCurrency')
            ->with(123, self::CURRENCY)
            ->willReturn('123$');

        $this->assertEquals(false, $this->formatter->isCurrencySymbolPrepend(self::CURRENCY, self::LOCALE));
        $this->assertEquals(false, $this->formatter->isCurrencySymbolPrepend(self::CURRENCY, self::LOCALE));
    }
}
