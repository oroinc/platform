<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Factory;

use NumberFormatter as IntlNumberFormatter;
use Oro\Bundle\LocaleBundle\Formatter\Factory\IntlNumberFormatterFactory;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Intl\Util\IntlTestHelper;

class IntlNumberFormatterFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const LOCALE = 'en_US';

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var IntlNumberFormatterFactory */
    private $factory;

    protected function setUp(): void
    {
        IntlTestHelper::requireIntl($this);

        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->factory = new IntlNumberFormatterFactory($this->localeSettings);
    }

    /**
     * @dataProvider invalidStyleDataProvider
     *
     * @param int|string $style
     */
    public function testCreateWhenInvalidStyle($style): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NumberFormatter style \'' . $style . '\' is invalid');

        $this->factory->create(self::LOCALE, $style, [], [], []);
    }

    public function invalidStyleDataProvider(): array
    {
        return [
            ['FRACTION_DIGITS'],
            [IntlNumberFormatter::FRACTION_DIGITS],
        ];
    }

    public function testCreateWhenInvalidConstant(): void
    {
        $style = 'invalid';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NumberFormatter has no constant \'invalid\'');

        $this->factory->create(self::LOCALE, $style, [], [], []);
    }

    /**
     * @dataProvider createAllStylesDataProvider
     *
     * @param string $style
     * @param int $expectedStyle
     */
    public function testCreateAllStyles($style, int $expectedStyle): void
    {
        $intlNumberFormatter = $this->factory->create(self::LOCALE, $style, [], [], []);

        $this->assertEquals(new IntlNumberFormatter(self::LOCALE, $expectedStyle), $intlNumberFormatter);
    }

    public function createAllStylesDataProvider(): array
    {
        return [
            ['style' => 'DECIMAL', 'expectedStyle' => IntlNumberFormatter::DECIMAL],
            ['style' => 'currency', 'expectedStyle' => IntlNumberFormatter::CURRENCY],
            ['style' => 'PERCENT', 'expectedStyle' => IntlNumberFormatter::PERCENT],
            ['style' => 'scientific', 'expectedStyle' => IntlNumberFormatter::SCIENTIFIC],
            ['style' => 'SPELLOUT', 'expectedStyle' => IntlNumberFormatter::SPELLOUT],
            ['style' => 'ordinal', 'expectedStyle' => IntlNumberFormatter::ORDINAL],
            ['style' => 'DURATION', 'expectedStyle' => IntlNumberFormatter::DURATION],
            ['style' => 'IGNORE', 'expectedStyle' => IntlNumberFormatter::IGNORE],
            ['style' => 'default_style', 'expectedStyle' => IntlNumberFormatter::DEFAULT_STYLE],
            ['style' => IntlNumberFormatter::DECIMAL, 'expectedStyle' => IntlNumberFormatter::DECIMAL],
            ['style' => IntlNumberFormatter::CURRENCY, 'expectedStyle' => IntlNumberFormatter::CURRENCY],
            ['style' => IntlNumberFormatter::PERCENT, 'expectedStyle' => IntlNumberFormatter::PERCENT],
            ['style' => IntlNumberFormatter::SCIENTIFIC, 'expectedStyle' => IntlNumberFormatter::SCIENTIFIC],
            ['style' => IntlNumberFormatter::SPELLOUT, 'expectedStyle' => IntlNumberFormatter::SPELLOUT],
            ['style' => IntlNumberFormatter::ORDINAL, 'expectedStyle' => IntlNumberFormatter::ORDINAL],
            ['style' => IntlNumberFormatter::DURATION, 'expectedStyle' => IntlNumberFormatter::DURATION],
            ['style' => IntlNumberFormatter::IGNORE, 'expectedStyle' => IntlNumberFormatter::IGNORE],
            ['style' => IntlNumberFormatter::DEFAULT_STYLE, 'expectedStyle' => IntlNumberFormatter::DEFAULT_STYLE],
        ];
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param int|string $style
     * @param array $attributes
     * @param array $textAttributes
     * @param array $symbols
     * @param array $expectedAttribute
     * @param array $expectedTextAttribute
     * @param array $expectedSymbol
     */
    public function testCreate(
        $style,
        array $attributes,
        array $textAttributes,
        array $symbols,
        array $expectedAttribute,
        array $expectedTextAttribute,
        array $expectedSymbol
    ): void {
        $intlNumberFormatter = $this->factory->create(self::LOCALE, $style, $attributes, $textAttributes, $symbols);

        $this->assertEquals(
            current($expectedAttribute),
            $intlNumberFormatter->getAttribute(key($expectedAttribute))
        );
        $this->assertEquals(
            current($expectedTextAttribute),
            $intlNumberFormatter->getTextAttribute(key($expectedTextAttribute))
        );
        $this->assertEquals(
            current($expectedSymbol),
            $intlNumberFormatter->getSymbol(key($expectedSymbol))
        );
    }

    public function createDataProvider(): array
    {
        return [
            [
                'style' => 'decimal',
                'attributes' => ['MAX_INTEGER_DIGITS' => 5],
                'textAttributes' => ['NEGATIVE_PREFIX' => '--'],
                'symbols' => ['GROUPING_SEPARATOR_SYMBOL' => '|'],
                'expectedAttribute' => [IntlNumberFormatter::MAX_INTEGER_DIGITS => 5],
                'expectedTextAttribute' => [IntlNumberFormatter::NEGATIVE_PREFIX => '--'],
                'expectedSymbol' => [IntlNumberFormatter::GROUPING_SEPARATOR_SYMBOL => '|'],
            ],
            [
                'style' => 'percent',
                'attributes' => [IntlNumberFormatter::MAX_INTEGER_DIGITS => 5],
                'textAttributes' => [IntlNumberFormatter::NEGATIVE_PREFIX => '--'],
                'symbols' => [IntlNumberFormatter::GROUPING_SEPARATOR_SYMBOL => '|'],
                'expectedAttribute' => [IntlNumberFormatter::MAX_INTEGER_DIGITS => 5],
                'expectedTextAttribute' => [IntlNumberFormatter::NEGATIVE_PREFIX => '--'],
                'expectedSymbol' => [IntlNumberFormatter::GROUPING_SEPARATOR_SYMBOL => '|'],
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param int|string $style
     * @param array $attributes
     * @param array $textAttributes
     * @param array $symbols
     * @param array $expectedAttribute
     * @param array $expectedTextAttribute
     * @param array $expectedSymbol
     */
    public function testCreateWhenNoLocale(
        $style,
        array $attributes,
        array $textAttributes,
        array $symbols,
        array $expectedAttribute,
        array $expectedTextAttribute,
        array $expectedSymbol
    ): void {
        $this->localeSettings
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        $intlNumberFormatter = $this->factory->create('', $style, $attributes, $textAttributes, $symbols);

        $this->assertEquals(
            current($expectedAttribute),
            $intlNumberFormatter->getAttribute(key($expectedAttribute))
        );
        $this->assertEquals(
            current($expectedTextAttribute),
            $intlNumberFormatter->getTextAttribute(key($expectedTextAttribute))
        );
        $this->assertEquals(
            current($expectedSymbol),
            $intlNumberFormatter->getSymbol(key($expectedSymbol))
        );
    }

    /**
     * @dataProvider createWhenPercentDataProvider
     */
    public function testCreateWhenPercent(array $attributes, array $expectedAttributes): void
    {
        $intlNumberFormatter = $this->factory->create(
            self::LOCALE,
            IntlNumberFormatter::PERCENT,
            $attributes,
            [],
            []
        );

        $this->assertEquals(
            $expectedAttributes[IntlNumberFormatter::MAX_INTEGER_DIGITS],
            $intlNumberFormatter->getAttribute(IntlNumberFormatter::MAX_INTEGER_DIGITS)
        );
        $this->assertEquals(
            $expectedAttributes[IntlNumberFormatter::FRACTION_DIGITS],
            $intlNumberFormatter->getAttribute(IntlNumberFormatter::FRACTION_DIGITS)
        );
        $this->assertEquals(
            $expectedAttributes[IntlNumberFormatter::MIN_FRACTION_DIGITS],
            $intlNumberFormatter->getAttribute(IntlNumberFormatter::MIN_FRACTION_DIGITS)
        );
        $this->assertEquals(
            $expectedAttributes[IntlNumberFormatter::MAX_FRACTION_DIGITS],
            $intlNumberFormatter->getAttribute(IntlNumberFormatter::MAX_FRACTION_DIGITS)
        );
    }

    public function createWhenPercentDataProvider(): array
    {
        $percentIntlNumberFormatter = new IntlNumberFormatter(self::LOCALE, IntlNumberFormatter::PERCENT);
        $maxIntegerDigits = $percentIntlNumberFormatter->getAttribute(IntlNumberFormatter::MAX_INTEGER_DIGITS);

        $decimalIntlNumberFormatter = new IntlNumberFormatter(self::LOCALE, IntlNumberFormatter::DECIMAL);
        $fractionDigits = $decimalIntlNumberFormatter->getAttribute(IntlNumberFormatter::FRACTION_DIGITS);
        $minFractionDigits = $decimalIntlNumberFormatter->getAttribute(IntlNumberFormatter::MIN_FRACTION_DIGITS);
        $maxFractionDigits = $decimalIntlNumberFormatter->getAttribute(IntlNumberFormatter::MAX_FRACTION_DIGITS);

        return [
            [
                'attributes' => ['MAX_INTEGER_DIGITS' => 5],
                'expectedAttributes' => [
                    IntlNumberFormatter::MAX_INTEGER_DIGITS => 5,
                    IntlNumberFormatter::FRACTION_DIGITS => $fractionDigits,
                    IntlNumberFormatter::MIN_FRACTION_DIGITS => $minFractionDigits,
                    IntlNumberFormatter::MAX_FRACTION_DIGITS => $maxFractionDigits,
                ],
            ],
            [
                'attributes' => [IntlNumberFormatter::MAX_INTEGER_DIGITS => 5],
                'expectedAttributes' => [
                    IntlNumberFormatter::MAX_INTEGER_DIGITS => 5,
                    IntlNumberFormatter::FRACTION_DIGITS => $fractionDigits,
                    IntlNumberFormatter::MIN_FRACTION_DIGITS => $minFractionDigits,
                    IntlNumberFormatter::MAX_FRACTION_DIGITS => $maxFractionDigits,
                ],
            ],
            [
                'attributes' => [],
                'expectedAttributes' => [
                    IntlNumberFormatter::MAX_INTEGER_DIGITS => $maxIntegerDigits,
                    IntlNumberFormatter::FRACTION_DIGITS => $fractionDigits,
                    IntlNumberFormatter::MIN_FRACTION_DIGITS => $minFractionDigits,
                    IntlNumberFormatter::MAX_FRACTION_DIGITS => $maxFractionDigits,
                ],
            ],
            [
                'attributes' => [
                    IntlNumberFormatter::FRACTION_DIGITS => 4,
                    IntlNumberFormatter::MIN_FRACTION_DIGITS => 4,
                    IntlNumberFormatter::MAX_FRACTION_DIGITS => 5,
                ],
                'expectedAttributes' => [
                    IntlNumberFormatter::MAX_INTEGER_DIGITS => $maxIntegerDigits,
                    IntlNumberFormatter::FRACTION_DIGITS => 4,
                    IntlNumberFormatter::MIN_FRACTION_DIGITS => 4,
                    IntlNumberFormatter::MAX_FRACTION_DIGITS => 5,
                ],
            ],
        ];
    }
}
