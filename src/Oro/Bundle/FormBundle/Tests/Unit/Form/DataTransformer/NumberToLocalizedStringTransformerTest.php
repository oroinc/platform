<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\DataTransformer\NumberToLocalizedStringTransformer;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class NumberToLocalizedStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    protected function setUp(): void
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
    }

    public function testTransformNullValue(): void
    {
        $this->numberFormatter->expects(self::never())
            ->method($this->anything());

        $transformer = new NumberToLocalizedStringTransformer($this->numberFormatter);
        self::assertEquals('', $transformer->transform(null));
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(
        float $from,
        string $to,
        int $scale = null,
        ?bool $grouping = false,
        ?int $roundingMode = NumberToLocalizedStringTransformer::ROUND_HALF_UP,
        string $locale = null
    ): void {
        if (null !== $scale) {
            $attributes = [
                \NumberFormatter::FRACTION_DIGITS => $scale,
                \NumberFormatter::ROUNDING_MODE => $roundingMode
            ];
        }
        $attributes[\NumberFormatter::GROUPING_USED] = $grouping;

        $this->numberFormatter->expects(self::once())
            ->method('formatDecimal')
            ->with($from, $attributes, [], [], $locale)
            ->willReturn($to);

        $transformer = new NumberToLocalizedStringTransformer(
            $this->numberFormatter,
            $scale,
            $grouping,
            $roundingMode,
            $locale
        );
        self::assertSame($to, $transformer->transform($from));
    }

    public function transformDataProvider(): array
    {
        return [
            [1234.123456789, '1234.123456789'],
            [1234.123456789, '1234.123456789', 2],
            [1234.123456789, '1,234.123456789', 2, true],
            [1234.123456789, '1.234,123456789', 2, true, NumberToLocalizedStringTransformer::ROUND_HALF_UP, 'de_DE'],
        ];
    }

    /**
     * @param string|null $from
     * @param float|string $to
     * @param int|null $scale
     * @param bool|null $grouping
     * @param int|null $roundingMode
     * @param string|null $locale
     * @param string $decimalSeparator
     * @param string $groupingSeparator
     *
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(
        ?string $from,
        $to,
        int $scale = null,
        ?bool $grouping = false,
        ?int $roundingMode = NumberToLocalizedStringTransformer::ROUND_HALF_UP,
        string $locale = 'en',
        string $decimalSeparator = '.',
        string $groupingSeparator = ','
    ): void {
        $this->numberFormatter->expects(self::any())
            ->method('getSymbol')
            ->withConsecutive(
                [\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, \NumberFormatter::DECIMAL],
                [\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, \NumberFormatter::DECIMAL]
            )
            ->willReturnOnConsecutiveCalls($decimalSeparator, $groupingSeparator);

        \Locale::setDefault($locale);

        $transformer = new NumberToLocalizedStringTransformer(
            $this->numberFormatter,
            $scale,
            $grouping,
            $roundingMode,
            $locale
        );
        self::assertSame($to, $transformer->reverseTransform($from));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            [null, null],
            ['', null],
            ['1234.123456789', 1234.123456789],
            ['1234.123', 1234.123, 3],
            ['1234.123456789', '1234.123456789', 3],
            ['1,234.123', 1234.123, 3, true, NumberToLocalizedStringTransformer::ROUND_HALF_UP],
            ['1,234.123456789123456789123456789', '1234.123456789123456789123456789', 3, true],
            [
                '1.234,123',
                1234.123,
                3,
                true,
                NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                'de_DE',
                ',',
                '.'
            ],
            [
                '1.234,123456789',
                '1234.123456789',
                3,
                true,
                NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                'de_DE',
                ',',
                '.'
            ],
            [
                '1.234,9999',
                1234.9999,
                4,
                true,
                NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                'de_DE',
                ',',
                '.'
            ],

            [
                '1.234,99999',
                '1234.99999',
                4,
                true,
                NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                'de_DE',
                ',',
                '.'
            ],
        ];
    }
}
