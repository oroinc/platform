<?php

namespace Oro\Bundle\LocaleBundle\Formatter\Factory;

use NumberFormatter as IntlNumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Tools\NumberFormatterHelper;

/**
 * Creates instance of NumberFormatter from intl extension.
 */
class IntlNumberFormatterFactory
{
    /** @var LocaleSettings */
    private $localeSettings;

    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * @param string $locale
     * @param int|string|null $style
     * @param array $attributes
     * @param array $textAttributes
     * @param array $symbols
     * @return IntlNumberFormatter
     * @throws \InvalidArgumentException
     */
    public function create(
        string $locale,
        $style,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = []
    ): IntlNumberFormatter {
        $locale = $locale ?: $this->localeSettings->getLocale();
        $style = $this->parseStyle($style);
        $attributes = $this->parseAttributes($attributes);
        $textAttributes = $this->parseAttributes($textAttributes);
        $symbols = $this->parseAttributes($symbols);

        $formatter = new IntlNumberFormatter($locale, $style);

        foreach ($attributes as $attribute => $value) {
            $formatter->setAttribute($attribute, $value);
        }

        foreach ($textAttributes as $attribute => $value) {
            $formatter->setTextAttribute($attribute, $value);
        }

        foreach ($symbols as $symbol => $value) {
            $formatter->setSymbol($symbol, $value);
        }

        $this->adjustFormatter($formatter, $locale, $style, $attributes);

        return $formatter;
    }

    /**
     * Parse style of NumberFormatter
     *
     * @param int|string|null $style
     * @return int
     * @throws \InvalidArgumentException
     */
    private function parseStyle($style): int
    {
        $originalValue = $style;
        $style = $originalValue ?? IntlNumberFormatter::DEFAULT_STYLE;

        $style = NumberFormatterHelper::parseConstantValue($style);

        $styleConstants = [
            IntlNumberFormatter::PATTERN_DECIMAL,
            IntlNumberFormatter::DECIMAL,
            IntlNumberFormatter::CURRENCY,
            IntlNumberFormatter::PERCENT,
            IntlNumberFormatter::SCIENTIFIC,
            IntlNumberFormatter::SPELLOUT,
            IntlNumberFormatter::ORDINAL,
            IntlNumberFormatter::DURATION,
            IntlNumberFormatter::PATTERN_RULEBASED,
            IntlNumberFormatter::IGNORE,
            IntlNumberFormatter::DEFAULT_STYLE,
        ];

        if (!in_array($style, $styleConstants, false)) {
            throw new \InvalidArgumentException(sprintf('NumberFormatter style \'%s\' is invalid', $originalValue));
        }

        return $style;
    }

    /**
     * Converts keys of attributes array to values of NumberFormatter constants
     *
     * @throws \InvalidArgumentException
     */
    private function parseAttributes(array $attributes): array
    {
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[NumberFormatterHelper::parseConstantValue($attribute)] = $value;
        }

        return $result;
    }

    private function adjustFormatter(
        IntlNumberFormatter $formatter,
        string $locale,
        int $style,
        array $attributes = []
    ): void {
        // Need to manually set percent fraction same to decimal
        if ($style === IntlNumberFormatter::PERCENT) {
            $overriddenDecimalAttributes = [
                IntlNumberFormatter::MIN_FRACTION_DIGITS,
                IntlNumberFormatter::MAX_FRACTION_DIGITS,
            ];

            $decimalFormatter = $this->create($locale, IntlNumberFormatter::DECIMAL);

            foreach ($overriddenDecimalAttributes as $decimalAttribute) {
                if (!array_key_exists($decimalAttribute, $attributes)) {
                    $formatter->setAttribute($decimalAttribute, $decimalFormatter->getAttribute($decimalAttribute));
                }
            }
        }
    }
}
