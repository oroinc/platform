<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Brick\Math\BigDecimal;
use NumberFormatter as IntlNumberFormatter;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\LocaleBundle\Formatter\Factory\IntlNumberFormatterFactory;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Tools\NumberFormatterHelper;

/**
 * Used to format numbers, currencies, percents etc according to locale and additional parameters
 */
class NumberFormatter
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * [
     *     '<locale>_<currencyCode>' => true|false|null,
     *     ...
     * ]
     *
     * @var array
     */
    protected $currencySymbolPrepend = [];

    /**
     * @var IntlNumberFormatterFactory
     */
    private $numberFormatterFactory;

    /** @var IntlNumberFormatter[] */
    protected $formatters = [];

    /** @var array */
    protected $currencySymbols = [];

    public function __construct(LocaleSettings $localeSettings, IntlNumberFormatterFactory $intlNumberFormatterFactory)
    {
        $this->localeSettings = $localeSettings;
        $this->numberFormatterFactory = $intlNumberFormatterFactory;
    }

    /**
     * Format number
     *
     * @param int|float $value
     * @param string|int $style Constant of IntlNumberFormatter (DECIMAL, CURRENCY, PERCENT, etc) or string name
     * @param array $attributes Set of attributes of IntlNumberFormatter
     * @param array $textAttributes Set of text attributes of IntlNumberFormatter
     * @param array $symbols Set of symbols of IntlNumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function format(
        $value,
        $style,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null
    ) {
        try {
            return $this->getFormatter($locale, $style, $attributes, $textAttributes, $symbols)->format($value);
        } catch (\TypeError $error) {
            return $value;
        }
    }

    /**
     * Format currency, replace INTL currency symbol with configuration currency symbol
     *
     * @param float $value
     * @param string|null $currencyCode Currency code
     * @param array $attributes Set of attributes of IntlNumberFormatter
     * @param array $textAttributes Set of text attributes of IntlNumberFormatter
     * @param array $symbols Set of symbols of IntlNumberFormatter
     * @param string|null $locale Locale of formatting
     *
     * @return string
     */
    public function formatCurrency(
        $value,
        $currencyCode = null,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null
    ) {
        if ($value instanceof MultiCurrency) {
            $value = $value->getValue();
        }
        if (!$currencyCode) {
            $currencyCode = $this->localeSettings->getCurrency();
        }

        if (!$locale) {
            $locale = $this->localeSettings->getLocale();
        }

        $formatter = $this->getFormatter(
            $locale . '@currency=' . $currencyCode,
            IntlNumberFormatter::CURRENCY,
            $attributes,
            $textAttributes,
            $symbols
        );

        $fixedFraction = array_key_exists(IntlNumberFormatter::MIN_FRACTION_DIGITS, $attributes);
        $formattedString = $this->formatCurrencyWithDynamicPrecision($formatter, $value, $currencyCode, $fixedFraction);
        $fromCurrencySymbol = $formatter->getSymbol(IntlNumberFormatter::CURRENCY_SYMBOL);
        $toCurrencySymbol = $this->getCurrencySymbolByCurrency($currencyCode, $locale);

        if ($toCurrencySymbol === $currencyCode) {
            // Adds a space after currency if it is an ISO code.
            $toCurrencySymbol .= ' ';

            // 1) replaces currency symbol with one provided by LocaleSettings;
            // 2) excludes the case with space duplication when space is already there.
            $formattedString = trim(
                str_replace(
                    [$fromCurrencySymbol, '  '],
                    [$toCurrencySymbol, ' '],
                    $formattedString
                ),
                ' '
            );
        }

        return $formattedString;
    }

    private function formatCurrencyWithDynamicPrecision(
        IntlNumberFormatter $currencyFormatter,
        ?float $value,
        string $currencyCode,
        bool $fixedFraction = false
    ): string {
        if (!$value || ((int)$value == $value) || $fixedFraction) {
            if ($value === null) {
                $value = 0;
            }

            return $currencyFormatter->formatCurrency($value, $currencyCode);
        }
        $decimalObject = BigDecimal::of($value);

        /**
         * The number of fraction digits cannot be less than the number of fraction digits specified in the
         * configuration of a particular currency.
         */
        $defaultFractionDigits = $currencyFormatter->getAttribute(\NumberFormatter::MIN_FRACTION_DIGITS);
        $fractionDigits = $defaultFractionDigits > $decimalObject->getScale()
            ? $defaultFractionDigits
            : $decimalObject->getScale();

        $currencyFormatter->setAttribute(IntlNumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
        $formattedString = $currencyFormatter->formatCurrency($decimalObject->toFloat(), $currencyCode);
        $currencyFormatter->setAttribute(IntlNumberFormatter::MIN_FRACTION_DIGITS, $defaultFractionDigits);

        return $formattedString;
    }

    private function getCurrencySymbolByCurrency(string $currencyCode, string $locale): string
    {
        if (!isset($this->currencySymbols[$currencyCode][$locale])) {
            $this->currencySymbols[$currencyCode][$locale] = $this->localeSettings->getCurrencySymbolByCurrency(
                $currencyCode,
                $locale
            );
        }

        return $this->currencySymbols[$currencyCode][$locale];
    }

    /**
     * Format decimal
     *
     * @param float|string|null $value
     * @param array $attributes Set of attributes of IntlNumberFormatter
     * @param array $textAttributes Set of text attributes of IntlNumberFormatter
     * @param array $symbols Set of symbols of IntlNumberFormatter
     * @param string|null $locale Locale of formatting
     *
     * @return string
     */
    public function formatDecimal(
        $value,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null
    ) {
        if ($value === '') {
            $value = null;
        }

        $formatter = $this->getFormatter($locale, IntlNumberFormatter::DECIMAL, $attributes, $textAttributes, $symbols);
        $formattedValue = $formatter->format($value);

        if ($value === null) {
            return $formattedValue;
        }

        return $this->formatDecimalWithDynamicScale(
            $formatter,
            $value,
            $formattedValue
        );
    }

    /**
     * We should leave fractional part of the number "as is"
     * in case this part has bigger scale than localized formatted value.
     *
     * @param IntlNumberFormatter $formatter
     * @param float|string $originValue
     * @param string $formattedValue
     *
     * @return string
     */
    private function formatDecimalWithDynamicScale(
        IntlNumberFormatter $formatter,
        $originValue,
        string $formattedValue
    ): string {
        $decimalSeparator = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        $explodedValue = explode($decimalSeparator, $formattedValue);
        $formattedValueScale = isset($explodedValue[1]) ? strlen($explodedValue[1]) : 0;
        $originValueBigDecimal = BigDecimal::of($originValue)->stripTrailingZeros();

        if ($originValueBigDecimal->getScale() > $formattedValueScale) {
            // Saved origin attribute value.
            $originalAttribute = $formatter->getAttribute(IntlNumberFormatter::MIN_FRACTION_DIGITS);

            // Formats integral part.
            $formatter->setAttribute(IntlNumberFormatter::MIN_FRACTION_DIGITS, 0);
            $sign = $originValueBigDecimal->getSign() === -1 ? '-' : '';
            $formattedValueIntegralPart = $formatter->format($originValueBigDecimal->abs()->getIntegralPart());

            // Restores original attribute value.
            $formatter->setAttribute(IntlNumberFormatter::MIN_FRACTION_DIGITS, $originalAttribute);

            $formattedValue = sprintf(
                '%s%s%s%s',
                $sign,
                $formattedValueIntegralPart,
                $decimalSeparator,
                $originValueBigDecimal->getFractionalPart()
            );
        }

        return $formattedValue;
    }

    /**
     * @param string $value
     * @param array  $attributes
     * @param array  $textAttributes
     * @param array  $symbols
     * @param null   $locale
     *
     * @return bool|float
     */
    public function parseFormattedDecimal(
        $value,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array(),
        $locale = null
    ) {
        $formatter = $this->getFormatter(
            $locale,
            IntlNumberFormatter::DECIMAL,
            $attributes,
            $textAttributes,
            $symbols
        );

        return $formatter->parse($value);
    }

    /**
     * Format percent
     *
     * @param float $value
     * @param array $attributes Set of attributes of IntlNumberFormatter
     * @param array $textAttributes Set of text attributes of IntlNumberFormatter
     * @param array $symbols Set of symbols of IntlNumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function formatPercent(
        $value,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null
    ) {
        if (!isset($attributes[IntlNumberFormatter::MAX_FRACTION_DIGITS])) {
            $attributes[IntlNumberFormatter::MAX_FRACTION_DIGITS] = BigDecimal::of($value)->getScale() - 2;
        }

        return $this->format($value, IntlNumberFormatter::PERCENT, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Format spellout
     *
     * @param float $value
     * @param array $attributes Set of attributes of IntlNumberFormatter
     * @param array $textAttributes Set of text attributes of IntlNumberFormatter
     * @param array $symbols Set of symbols of IntlNumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function formatSpellout(
        $value,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null
    ) {
        return $this->format($value, IntlNumberFormatter::SPELLOUT, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Format duration
     *
     * @param float|\DateTime $value If value is a DateTime then it's timestamp will be used.
     * @param array $attributes Set of attributes of IntlNumberFormatter
     * @param array $textAttributes Set of text attributes of IntlNumberFormatter
     * @param array $symbols Set of symbols of IntlNumberFormatter
     * @param string|null $locale Locale of formatting
     * @param bool $useDefaultFormat
     *
     * @return string
     */
    public function formatDuration(
        $value,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null,
        $useDefaultFormat = false
    ) {
        if ($value instanceof \DateTime) {
            $value = $value->getTimestamp();
        }
        $value = abs((float) $value);

        if ($useDefaultFormat) {
            return $this->formatDefaultDuration($value);
        }

        $result = $this->format($value, IntlNumberFormatter::DURATION, $attributes, $textAttributes, $symbols, $locale);

        // In case if the result is not a valid duration string, do default format
        if (!$this->isDurationValid($result)) {
            $result = $this->formatDefaultDuration($value);
        }

        return $result;
    }

    /**
     * Checks if duration is not valid.
     *
     * For some locales intl's Number formatter returns duration in format of simple numbers, for example for duration
     * of 1 hour 1 minute and 1 second the result could be a string "3 661". This method checks such cases, because
     * a valid localized duration string should be something like this "1:01:01" or "1 hour, 1 minute, 1 second"
     * depending on attributes of formatting.
     *
     * @param string $value
     * @return bool
     */
    protected function isDurationValid($value)
    {
        $stripChars = [
            ',',
            '.',
            ' ',
            $this->getSymbol(IntlNumberFormatter::GROUPING_SEPARATOR_SYMBOL, IntlNumberFormatter::DEFAULT_STYLE),
            $this->getSymbol(IntlNumberFormatter::DECIMAL_SEPARATOR_SYMBOL, IntlNumberFormatter::DEFAULT_STYLE),
        ];

        return !is_numeric(str_replace($stripChars, '', $value));
    }

    /**
     * Format duration to H:i:s format
     *
     * @param float $value
     * @return string
     */
    protected function formatDefaultDuration($value)
    {
        return
            str_pad((string)floor($value / 3600), 2, '0', STR_PAD_LEFT) . ':' .
            str_pad((string)(floor($value / 60)) % 60, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad((string)((int)$value) % 60, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Format ordinal
     *
     * @param float $value
     * @param array $attributes Set of attributes of IntlNumberFormatter
     * @param array $textAttributes Set of text attributes of IntlNumberFormatter
     * @param array $symbols Set of symbols of IntlNumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function formatOrdinal(
        $value,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null
    ) {
        return $this->format($value, IntlNumberFormatter::ORDINAL, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Gets value of numeric attribute of IntlNumberFormatter
     *
     * Supported numeric attribute constants of IntlNumberFormatter are:
     *  PARSE_INT_ONLY
     *  GROUPING_USED
     *  DECIMAL_ALWAYS_SHOWN
     *  MAX_INTEGER_DIGITS
     *  MIN_INTEGER_DIGITS
     *  INTEGER_DIGITS
     *  MAX_FRACTION_DIGITS
     *  MIN_FRACTION_DIGITS
     *  FRACTION_DIGITS
     *  MULTIPLIER
     *  GROUPING_SIZE
     *  ROUNDING_MODE
     *  ROUNDING_INCREMENT
     *  FORMAT_WIDTH
     *  PADDING_POSITION
     *  SECONDARY_GROUPING_SIZE
     *  SIGNIFICANT_DIGITS_USED
     *  MIN_SIGNIFICANT_DIGITS
     *  MAX_SIGNIFICANT_DIGITS
     *  LENIENT_PARSE
     *
     * @param int|string $attribute Numeric attribute constant of IntlNumberFormatter or it's string name
     * @param int|string $style Constant of IntlNumberFormatter (DECIMAL, CURRENCY, PERCENT, etc) or string name
     * @param string|null $locale
     * @param array $attributes
     * @return bool|int
     */
    public function getAttribute($attribute, $style = null, $locale = null, $attributes = [])
    {
        return $this->getFormatter($locale, $style, $attributes)
            ->getAttribute(NumberFormatterHelper::parseConstantValue($attribute));
    }

    /**
     * Gets value of text attribute of IntlNumberFormatter
     *
     * Supported text attribute constants of IntlNumberFormatter are:
     *  POSITIVE_PREFIX
     *  POSITIVE_SUFFIX
     *  NEGATIVE_PREFIX
     *  NEGATIVE_SUFFIX
     *  PADDING_CHARACTER
     *  CURRENCY_CODE
     *  DEFAULT_RULESET
     *  PUBLIC_RULESETS
     *
     * @param int|string $attribute Text attribute constant of IntlNumberFormatter or it's string name
     * @param int|string $style Constant of IntlNumberFormatter (DECIMAL, CURRENCY, PERCENT, etc) or string name
     * @param string|null $locale
     * @return bool|int
     */
    public function getTextAttribute($attribute, $style, $locale = null)
    {
        return $this->getFormatter($locale, $style)
            ->getTextAttribute(NumberFormatterHelper::parseConstantValue($attribute));
    }

    /**
     * Gets value of symbol associated with IntlNumberFormatter
     *
     * Supported symbol constants of IntlNumberFormatter are:
     *  DECIMAL_SEPARATOR_SYMBOL
     *  GROUPING_SEPARATOR_SYMBOL
     *  PATTERN_SEPARATOR_SYMBOL
     *  PERCENT_SYMBOL
     *  ZERO_DIGIT_SYMBOL
     *  DIGIT_SYMBOL
     *  MINUS_SIGN_SYMBOL
     *  PLUS_SIGN_SYMBOL
     *  CURRENCY_SYMBOL
     *  INTL_CURRENCY_SYMBOL
     *  MONETARY_SEPARATOR_SYMBOL
     *  EXPONENTIAL_SYMBOL
     *  PERMILL_SYMBOL
     *  PAD_ESCAPE_SYMBOL
     *  INFINITY_SYMBOL
     *  NAN_SYMBOL
     *  SIGNIFICANT_DIGIT_SYMBOL
     *  MONETARY_GROUPING_SEPARATOR_SYMBOL
     *
     * @param int|string $symbol Format symbol constant of IntlNumberFormatter or it's string name
     * @param int|string $style Constant of IntlNumberFormatter (DECIMAL, CURRENCY, PERCENT, etc) or string name
     * @param string|null $locale
     * @return bool|int
     */
    public function getSymbol($symbol, $style, $locale = null)
    {
        return $this->getFormatter($locale, $style)->getSymbol(NumberFormatterHelper::parseConstantValue($symbol));
    }

    /**
     * Creates instance of NumberFormatter class of intl extension
     *
     * @param string|null $locale
     * @param int|string $style
     * @param array $attributes
     * @param array $textAttributes
     * @param array $symbols
     * @return IntlNumberFormatter
     * @throws \InvalidArgumentException
     */
    protected function getFormatter(
        $locale,
        $style,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = []
    ): IntlNumberFormatter {
        $cacheKey = sha1(\json_encode(func_get_args()));
        if (!isset($this->formatters[$cacheKey])) {
            $this->formatters[$cacheKey] = $this->numberFormatterFactory->create(
                (string)$locale,
                $style,
                $attributes,
                $textAttributes,
                $symbols
            );
        }

        return $this->formatters[$cacheKey];
    }

    /**
     * @param string|null $currency
     * @param string|null $locale
     * @return bool|null Null means that there are no currency symbol in string
     */
    public function isCurrencySymbolPrepend($currency = null, $locale = null)
    {
        if (!$locale) {
            $locale = $this->localeSettings->getLocale();
        }

        if (!$currency) {
            $currency = $this->localeSettings->getCurrency();
        }

        $key = $locale . '_' . $currency;
        if (!array_key_exists($key, $this->currencySymbolPrepend)) {
            $formatter = $this->getFormatter($locale, IntlNumberFormatter::CURRENCY);
            $pattern = $formatter->formatCurrency('123', $currency);
            preg_match(
                '/^([^\s\xc2\xa0]*)[\s\xc2\xa0]*123(?:[,.]0+)?[\s\xc2\xa0]*([^\s\xc2\xa0]*)$/u',
                $pattern,
                $matches
            );

            if (!empty($matches[1])) {
                $this->currencySymbolPrepend[$key] = true;
            } elseif (!empty($matches[2])) {
                $this->currencySymbolPrepend[$key] = false;
            } else {
                $this->currencySymbolPrepend[$key] = null;
            }
        }

        return $this->currencySymbolPrepend[$key];
    }
}
