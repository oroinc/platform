<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions to access locale data:
 *   - oro_locale_number_attribute
 *   - oro_locale_number_text_attribute
 *   - oro_locale_number_symbol
 *   - oro_currency_symbol_prepend
 *
 * Provides Twig filters for number and currency formatting:
 *   - oro_format_number
 *   - oro_format_currency
 *   - oro_format_decimal
 *   - oro_format_percent
 *   - oro_format_spellout
 *   - oro_format_duration
 *   - oro_format_ordinal
 */
class NumberExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?NumberFormatter $numberFormatter = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_locale_number_attribute', [$this, 'getAttribute']),
            new TwigFunction('oro_locale_number_text_attribute', [$this, 'getTextAttribute']),
            new TwigFunction('oro_locale_number_symbol', [$this, 'getSymbol']),
            new TwigFunction('oro_currency_symbol_prepend', [$this, 'isCurrencySymbolPrepend'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('oro_format_number', [$this, 'format']),
            new TwigFilter('oro_format_currency', [$this, 'formatCurrency'], ['is_safe' => ['html']]),
            new TwigFilter('oro_format_decimal', [$this, 'formatDecimal']),
            new TwigFilter('oro_format_percent', [$this, 'formatPercent']),
            new TwigFilter('oro_format_spellout', [$this, 'formatSpellout']),
            new TwigFilter('oro_format_duration', [$this, 'formatDuration']),
            new TwigFilter('oro_format_ordinal', [$this, 'formatOrdinal']),
        ];
    }

    /**
     * Gets value of numeric attribute of \NumberFormatter
     *
     * @param string|int  $attribute
     * @param string|null $style
     * @param string|null $locale
     * @param array $attributes
     *
     * @return bool|int
     */
    public function getAttribute($attribute, $style = null, $locale = null, $attributes = [])
    {
        return $this->getNumberFormatter()->getAttribute($attribute, $style, $locale, $attributes);
    }

    /**
     * Gets value of text attribute of \NumberFormatter
     *
     * @param string|int  $attribute
     * @param string|null $style
     * @param string|null $locale
     *
     * @return bool|int
     */
    public function getTextAttribute($attribute, $style = null, $locale = null)
    {
        return $this->getNumberFormatter()->getTextAttribute($attribute, $style, $locale);
    }

    /**
     * Gets value of symbol associated with \NumberFormatter
     *
     * @param string|int  $symbol
     * @param string|null $style
     * @param string|null $locale
     *
     * @return bool|int
     */
    public function getSymbol($symbol, $style = null, $locale = null)
    {
        return $this->getNumberFormatter()->getSymbol($symbol, $style, $locale);
    }

    /**
     * Formats number according to locale settings.
     *
     * Options format:
     * array(
     *     'attributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'textAttributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'symbols' => array(
     *          <symbol> => <value>,
     *          ...
     *      ),
     *     'locale' => <locale>
     * )
     *
     * @param int|float  $value
     * @param int|string $style
     * @param array      $options
     *
     * @return string
     */
    public function format($value, $style, array $options = [])
    {
        return $this->getNumberFormatter()->format(
            $value,
            $style,
            (array)($options['attributes'] ?? []),
            (array)($options['textAttributes'] ?? []),
            (array)($options['symbols'] ?? []),
            $options['locale'] ?? null
        );
    }

    /**
     * Formats currency number according to locale settings.
     *
     * Options format:
     * array(
     *     'currency' => <currency>,
     *     'attributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'textAttributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'symbols' => array(
     *          <symbol> => <value>,
     *          ...
     *      ),
     *     'locale' => <locale>
     * )
     *
     * @param float $value
     * @param array $options
     *
     * @return string
     */
    public function formatCurrency($value, array $options = [])
    {
        $formattedValue = $this->getNumberFormatter()->formatCurrency(
            $value,
            $options['currency'] ?? null,
            (array)($options['attributes'] ?? []),
            (array)($options['textAttributes'] ?? []),
            (array)($options['symbols'] ?? []),
            $options['locale'] ?? null
        );

        return strip_tags($formattedValue);
    }

    /**
     * Formats decimal number according to locale settings.
     *
     * Options format:
     * array(
     *     'attributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'textAttributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'symbols' => array(
     *          <symbol> => <value>,
     *          ...
     *      ),
     *     'locale' => <locale>
     * )
     *
     * @param float $value
     * @param array $options
     *
     * @return string
     */
    public function formatDecimal($value, array $options = [])
    {
        return $this->getNumberFormatter()->formatDecimal(
            $value,
            (array)($options['attributes'] ?? []),
            (array)($options['textAttributes'] ?? []),
            (array)($options['symbols'] ?? []),
            $options['locale'] ?? null
        );
    }

    /**
     * Formats percent number according to locale settings.
     *
     * Options format:
     * array(
     *     'attributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'textAttributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'symbols' => array(
     *          <symbol> => <value>,
     *          ...
     *      ),
     *     'locale' => <locale>
     * )
     *
     * @param float $value
     * @param array $options
     *
     * @return string
     */
    public function formatPercent($value, array $options = [])
    {
        return $this->getNumberFormatter()->formatPercent(
            $value,
            (array)($options['attributes'] ?? []),
            (array)($options['textAttributes'] ?? []),
            (array)($options['symbols'] ?? []),
            $options['locale'] ?? null
        );
    }

    /**
     * Formats spellout number according to locale settings.
     *
     * Options format:
     * array(
     *     'attributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'textAttributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'symbols' => array(
     *          <symbol> => <value>,
     *          ...
     *      ),
     *     'locale' => <locale>
     * )
     *
     * @param float $value
     * @param array $options
     *
     * @return string
     */
    public function formatSpellout($value, array $options = [])
    {
        return $this->getNumberFormatter()->formatSpellout(
            $value,
            (array)($options['attributes'] ?? []),
            (array)($options['textAttributes'] ?? []),
            (array)($options['symbols'] ?? []),
            $options['locale'] ?? null
        );
    }

    /**
     * Formats duration number according to locale settings.
     *
     * Options format:
     * array(
     *     'attributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'textAttributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'symbols' => array(
     *          <symbol> => <value>,
     *          ...
     *      ),
     *     'locale' => <locale>,
     *     'default' => <value>
     * )
     *
     * @param float $value
     * @param array $options
     *
     * @return string
     */
    public function formatDuration($value, array $options = [])
    {
        return $this->getNumberFormatter()->formatDuration(
            $value,
            (array)($options['attributes'] ?? []),
            (array)($options['textAttributes'] ?? []),
            (array)($options['symbols'] ?? []),
            $options['locale'] ?? null,
            $options['default'] ?? false
        );
    }

    /**
     * Formats ordinal number according to locale settings.
     *
     * Options format:
     * array(
     *     'attributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'textAttributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'symbols' => array(
     *          <symbol> => <value>,
     *          ...
     *      ),
     *     'locale' => <locale>
     * )
     *
     * @param float $value
     * @param array $options
     *
     * @return string
     */
    public function formatOrdinal($value, array $options = [])
    {
        return $this->getNumberFormatter()->formatOrdinal(
            $value,
            (array)($options['attributes'] ?? []),
            (array)($options['textAttributes'] ?? []),
            (array)($options['symbols'] ?? []),
            $options['locale'] ?? null
        );
    }

    /**
     * @param string|null $currency
     * @param string|null $locale
     *
     * @return bool|null
     */
    public function isCurrencySymbolPrepend($currency = null, $locale = null)
    {
        return $this->getNumberFormatter()->isCurrencySymbolPrepend($currency, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_locale.formatter.number' => NumberFormatter::class,
        ];
    }

    private function getNumberFormatter(): NumberFormatter
    {
        if (null === $this->numberFormatter) {
            $this->numberFormatter = $this->container->get('oro_locale.formatter.number');
        }

        return $this->numberFormatter;
    }
}
