<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Brick\Math\BigDecimal;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

/**
 * Transforms between a number type and a localized number (or string in case when a number has bigger scale than float)
 * with grouping (each thousand) and comma separators.
 */
class NumberToLocalizedStringTransformer extends Symfony54NumberToLocalizedStringTransformer
{
    private NumberFormatter $numberFormatter;

    private ?int $scale;

    private ?string $locale;

    public function __construct(
        NumberFormatter $numberFormatter,
        int $scale = null,
        ?bool $grouping = false,
        ?int $roundingMode = \NumberFormatter::ROUND_HALFUP,
        string $locale = null
    ) {
        $this->numberFormatter = $numberFormatter;

        $this->scale = $scale;
        $this->locale = $locale;

        parent::__construct($scale, $grouping, $roundingMode, $locale);
    }

    #[\Override]
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        if (null !== $this->scale) {
            $attributes = [
                \NumberFormatter::FRACTION_DIGITS => $this->scale,
                \NumberFormatter::ROUNDING_MODE => $this->roundingMode
            ];
        }
        $attributes[\NumberFormatter::GROUPING_USED] = $this->grouping;

        return $this->numberFormatter->formatDecimal($value, $attributes, [], [], $this->locale);
    }

    #[\Override]
    public function reverseTransform($value): string|float|int|null
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return $this->reverseTransformNumberWithDynamicScale($value, parent::reverseTransform($value));
    }

    /**
     * We should leave fractional part of the number "as is"
     * in case this part has bigger scale than transformed value.
     *
     * @param string $originValue
     * @param int|float $formattedValue
     *
     * @return int|float|string
     */
    private function reverseTransformNumberWithDynamicScale(
        string $originValue,
        $formattedValue
    ) {
        $decimalSeparator = $this->numberFormatter->getSymbol(
            \NumberFormatter::DECIMAL_SEPARATOR_SYMBOL,
            \NumberFormatter::DECIMAL
        );
        $groupingSeparator = $this->numberFormatter->getSymbol(
            \NumberFormatter::GROUPING_SEPARATOR_SYMBOL,
            \NumberFormatter::DECIMAL
        );

        $value = strtr($originValue, [
            $groupingSeparator => '',
            $decimalSeparator => '.'
        ]);
        $originValueBigDecimal = BigDecimal::of($value)->stripTrailingZeros();
        $formattedValueBigDecimal = BigDecimal::of($formattedValue);

        if ($originValueBigDecimal->getScale() > $formattedValueBigDecimal->getScale()) {
            $formattedValue = sprintf(
                '%s%s%s%s',
                $originValueBigDecimal->getSign() === -1 ? '-' : '',
                $originValueBigDecimal->abs()->getIntegralPart(),
                '.',
                $originValueBigDecimal->getFractionalPart()
            );
        }

        return $formattedValue;
    }

    /**
     * @param $value
     *
     * @return int|float
     */
    #[\Override]
    protected function castParsedValue($value): float|int
    {
        return $value;
    }
}
