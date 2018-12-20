<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use NumberFormatter as IntlNumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

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
     * array(
     *      '<locale>' => array(
     *          '<currencyCode>' => true|false|null,
     *          ...
     *      ),
     *      ...
     * )
     *
     * @var array
     */
    protected $currencySymbolPrepend = array();

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * Format number
     *
     * @param int|float $value
     * @param string|int $style Constant of \NumberFormatter (DECIMAL, CURRENCY, PERCENT, etc) or string name
     * @param array $attributes Set of attributes of \NumberFormatter
     * @param array $textAttributes Set of text attributes of \NumberFormatter
     * @param array $symbols Set of symbols of \NumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function format(
        $value,
        $style,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array(),
        $locale = null
    ) {
        return $this->getFormatter($locale, $this->parseConstantValue($style), $attributes, $textAttributes, $symbols)
            ->format($value);
    }

    /**
     * Format currency, replace INTL currency symbol with configuration currency symbol
     *
     * @param float $value
     * @param string $currencyCode Currency code
     * @param array $attributes Set of attributes of \NumberFormatter
     * @param array $textAttributes Set of text attributes of \NumberFormatter
     * @param array $symbols Set of symbols of \NumberFormatter
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
        if (!$currencyCode) {
            $currencyCode = $this->localeSettings->getCurrency();
        }

        if (!$locale) {
            $locale = $this->localeSettings->getLocale();
        }

        $formatter = $this->getFormatter(
            $locale . '@currency=' . $currencyCode,
            \NumberFormatter::CURRENCY,
            $attributes,
            $textAttributes,
            $symbols
        );

        $formattedString = $formatter->formatCurrency($value, $currencyCode);
        $fromCurrencySymbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
        $toCurrencySymbol = $this->localeSettings->getCurrencySymbolByCurrency($currencyCode, $locale);

        if ($toCurrencySymbol === $currencyCode) {
            // Adds a space after currency if it is an ISO code.
            $toCurrencySymbol .= ' ';

            // 1) replaces currency symbol with one provided by LocaleSettings;
            // 2) excludes the case with space duplication when space is already there.
            $formattedString = trim(str_replace(
                [$fromCurrencySymbol, '  '],
                [$toCurrencySymbol, ' '],
                $formattedString
            ), ' ');
        }

        return $formattedString;
    }

    /**
     * Format decimal
     *
     * @param float $value
     * @param array $attributes Set of attributes of \NumberFormatter
     * @param array $textAttributes Set of text attributes of \NumberFormatter
     * @param array $symbols Set of symbols of \NumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function formatDecimal(
        $value,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array(),
        $locale = null
    ) {
        return $this->format($value, \NumberFormatter::DECIMAL, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Format percent
     *
     * @param float $value
     * @param array $attributes Set of attributes of \NumberFormatter
     * @param array $textAttributes Set of text attributes of \NumberFormatter
     * @param array $symbols Set of symbols of \NumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function formatPercent(
        $value,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array(),
        $locale = null
    ) {
        return $this->format($value, \NumberFormatter::PERCENT, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Format spellout
     *
     * @param float $value
     * @param array $attributes Set of attributes of \NumberFormatter
     * @param array $textAttributes Set of text attributes of \NumberFormatter
     * @param array $symbols Set of symbols of \NumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function formatSpellout(
        $value,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array(),
        $locale = null
    ) {
        return $this->format($value, \NumberFormatter::SPELLOUT, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Format duration
     *
     * @param float|\DateTime $value          If value is a DateTime then it's timestamp will be used.
     * @param array           $attributes     Set of attributes of \NumberFormatter
     * @param array           $textAttributes Set of text attributes of \NumberFormatter
     * @param array           $symbols        Set of symbols of \NumberFormatter
     * @param string|null     $locale         Locale of formatting
     * @param bool            $useDefaultFormat
     *
     * @return string
     */
    public function formatDuration(
        $value,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array(),
        $locale = null,
        $useDefaultFormat = false
    ) {
        if ($value instanceof \DateTime) {
            $value = $value->getTimestamp();
        }

        $value = abs($value);

        if ($useDefaultFormat) {
            return $this->formatDefaultDuration($value);
        }

        $result = $this->format($value, \NumberFormatter::DURATION, $attributes, $textAttributes, $symbols, $locale);

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
        $stripChars = array(
            ',',
            '.',
            ' ',
            $this->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, \NumberFormatter::DEFAULT_STYLE),
            $this->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, \NumberFormatter::DEFAULT_STYLE),
        );
        return !is_numeric(str_replace($stripChars, '', $value));
    }

    /**
     * Format duration to H:i:s format
     *
     * @param float
     * @return string
     */
    protected function formatDefaultDuration($value)
    {
        return
            str_pad(floor($value / 3600), 2, '0', STR_PAD_LEFT) . ':' .
            str_pad((floor($value / 60)) % 60, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($value % 60, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Format ordinal
     *
     * @param float $value
     * @param array $attributes Set of attributes of \NumberFormatter
     * @param array $textAttributes Set of text attributes of \NumberFormatter
     * @param array $symbols Set of symbols of \NumberFormatter
     * @param string|null $locale Locale of formatting
     * @return string
     */
    public function formatOrdinal(
        $value,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array(),
        $locale = null
    ) {
        return $this->format($value, \NumberFormatter::ORDINAL, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Gets value of numeric attribute of \NumberFormatter
     *
     * Supported numeric attribute constants of \NumberFormatter are:
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
     * @param int|string $attribute Numeric attribute constant of \NumberFormatter or it's string name
     * @param int|string $style Constant of \NumberFormatter (DECIMAL, CURRENCY, PERCENT, etc) or string name
     * @param string|null $locale
     * @param array $attributes
     * @return bool|int
     */
    public function getAttribute($attribute, $style = null, $locale = null, $attributes = [])
    {
        return $this->getFormatter(
            $locale,
            $this->parseStyle($style),
            $attributes
        )->getAttribute($this->parseConstantValue($attribute));
    }

    /**
     * Gets value of text attribute of \NumberFormatter
     *
     * Supported text attribute constants of \NumberFormatter are:
     *  POSITIVE_PREFIX
     *  POSITIVE_SUFFIX
     *  NEGATIVE_PREFIX
     *  NEGATIVE_SUFFIX
     *  PADDING_CHARACTER
     *  CURRENCY_CODE
     *  DEFAULT_RULESET
     *  PUBLIC_RULESETS
     *
     * @param int|string $attribute Text attribute constant of \NumberFormatter or it's string name
     * @param int|string $style Constant of \NumberFormatter (DECIMAL, CURRENCY, PERCENT, etc) or string name
     * @param string|null $locale
     * @return bool|int
     */
    public function getTextAttribute($attribute, $style, $locale = null)
    {
        return $this->getFormatter(
            $locale,
            $this->parseStyle($style)
        )->getTextAttribute($this->parseConstantValue($attribute));
    }

    /**
     * Gets value of symbol associated with \NumberFormatter
     *
     * Supported symbol constants of \NumberFormatter are:
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
     *
     * @param int|string $symbol Format symbol constant of \NumberFormatter or it's string name
     * @param int|string $style Constant of \NumberFormatter (DECIMAL, CURRENCY, PERCENT, etc) or string name
     * @param string|null $locale
     * @return bool|int
     */
    public function getSymbol($symbol, $style, $locale = null)
    {
        return $this->getFormatter(
            $locale,
            $this->parseStyle($style)
        )->getSymbol($this->parseConstantValue($symbol));
    }

    /**
     * Creates instance of NumberFormatter class of intl extension
     *
     * @param string $locale
     * @param int $style
     * @param array $attributes
     * @param array $textAttributes
     * @param array $symbols
     * @return IntlNumberFormatter
     * @throws \InvalidArgumentException
     */
    protected function getFormatter(
        $locale,
        $style,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array()
    ) {
        $locale = $locale ? : $this->localeSettings->getLocale();
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
     * @param IntlNumberFormatter $formatter
     * @param string $locale
     * @param int $style
     * @param array $attributes
     */
    protected function adjustFormatter(
        IntlNumberFormatter $formatter,
        $locale,
        $style,
        array $attributes = array()
    ) {
        // need to manually set percent fraction same to decimal
        if ($style === \NumberFormatter::PERCENT) {
            $overriddenDecimalAttributes = array(
                \NumberFormatter::FRACTION_DIGITS,
                \NumberFormatter::MIN_FRACTION_DIGITS,
                \NumberFormatter::MAX_FRACTION_DIGITS,
            );

            $decimalFormatter = $this->getFormatter($locale, \NumberFormatter::DECIMAL);

            foreach ($overriddenDecimalAttributes as $decimalAttribute) {
                if (!array_key_exists($decimalAttribute, $attributes)) {
                    $formatter->setAttribute($decimalAttribute, $decimalFormatter->getAttribute($decimalAttribute));
                }
            }
        }
    }

    /**
     * Converts keys of attributes array to values of NumberFormatter constants
     *
     * @param array $attributes
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function parseAttributes(array $attributes)
    {
        $result = array();
        foreach ($attributes as $attribute => $value) {
            $result[$this->parseConstantValue($attribute)] = $value;
        }
        return $result;
    }

    /**
     * Pass value of NumberFormatter constant or it's string name and get value
     *
     * @param int|string $attribute
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function parseConstantValue($attribute)
    {
        if (is_int($attribute)) {
            return $attribute;
        } else {
            $attributeName = strtoupper($attribute);
            $constantName = 'NumberFormatter::' . $attributeName;
            if (!defined($constantName)) {
                throw new \InvalidArgumentException("NumberFormatter has no constant '$attributeName'");
            }
            return constant($constantName);
        }
    }

    /**
     * Pass style of NumberFormatter
     *
     * @param int|string|null $style
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function parseStyle($style)
    {
        $originalValue = $style;
        if (null === $style) {
            $style = \NumberFormatter::DEFAULT_STYLE;
        }
        $style = $this->parseConstantValue($style);

        $styleConstants = array(
            \NumberFormatter::PATTERN_DECIMAL,
            \NumberFormatter::DECIMAL,
            \NumberFormatter::CURRENCY,
            \NumberFormatter::PERCENT,
            \NumberFormatter::SCIENTIFIC,
            \NumberFormatter::SPELLOUT,
            \NumberFormatter::ORDINAL,
            \NumberFormatter::DURATION,
            \NumberFormatter::PATTERN_RULEBASED,
            \NumberFormatter::IGNORE,
            \NumberFormatter::DEFAULT_STYLE,
        );

        if (!in_array($style, $styleConstants)) {
            throw new \InvalidArgumentException("NumberFormatter style '$originalValue' is invalid");
        }

        return $style;
    }

    /**
     * @param string $currency
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

        if (empty($this->currencySymbolPrepend[$locale])
            || !array_key_exists($currency, $this->currencySymbolPrepend)
        ) {
            $formatter = $this->getFormatter($locale, \NumberFormatter::CURRENCY);
            $pattern = $formatter->formatCurrency('123', $currency);
            preg_match(
                '/^([^\s\xc2\xa0]*)[\s\xc2\xa0]*123(?:[,.]0+)?[\s\xc2\xa0]*([^\s\xc2\xa0]*)$/u',
                $pattern,
                $matches
            );

            if (!empty($matches[1])) {
                $this->currencySymbolPrepend[$locale][$currency] = true;
            } elseif (!empty($matches[2])) {
                $this->currencySymbolPrepend[$locale][$currency] = false;
            } else {
                $this->currencySymbolPrepend[$locale][$currency] = null;
            }
        }

        return $this->currencySymbolPrepend[$locale][$currency];
    }
}
