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
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return NumberFormatter
     */
    protected function getNumberFormatter()
    {
        return $this->container->get('oro_locale.formatter.number');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_locale_number_attribute',
                [$this, 'getAttribute']
            ),
            new TwigFunction(
                'oro_locale_number_text_attribute',
                [$this, 'getTextAttribute']
            ),
            new TwigFunction(
                'oro_locale_number_symbol',
                [$this, 'getSymbol']
            ),
            new TwigFunction(
                'oro_currency_symbol_prepend',
                [$this, 'isCurrencySymbolPrepend']
            ),
            new TwigFunction(
                'oro_locale_allow_to_round_displayed_prices_and_amounts',
                [$this, 'isAllowedToRoundPricesAndAmounts']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'oro_format_number',
                [$this, 'format']
            ),
            new TwigFilter(
                'oro_format_currency',
                [$this, 'formatCurrency'],
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'oro_format_decimal',
                [$this, 'formatDecimal']
            ),
            new TwigFilter(
                'oro_format_percent',
                [$this, 'formatPercent']
            ),
            new TwigFilter(
                'oro_format_spellout',
                [$this, 'formatSpellout']
            ),
            new TwigFilter(
                'oro_format_duration',
                [$this, 'formatDuration']
            ),
            new TwigFilter(
                'oro_format_ordinal',
                [$this, 'formatOrdinal']
            ),
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
        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');

        return $this->getNumberFormatter()
            ->format($value, $style, $attributes, $textAttributes, $symbols, $locale);
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
        $currency = $this->getOption($options, 'currency');
        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');

        $formattedValue = $this->getNumberFormatter()
            ->formatCurrency($value, $currency, $attributes, $textAttributes, $symbols, $locale);

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
        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');

        return $this->getNumberFormatter()
            ->formatDecimal($value, $attributes, $textAttributes, $symbols, $locale);
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
        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');

        return $this->getNumberFormatter()
            ->formatPercent($value, $attributes, $textAttributes, $symbols, $locale);
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
        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');

        return $this->getNumberFormatter()
            ->formatSpellout($value, $attributes, $textAttributes, $symbols, $locale);
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
        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');
        $default = $this->getOption($options, 'default', false);

        return $this->getNumberFormatter()
            ->formatDuration($value, $attributes, $textAttributes, $symbols, $locale, $default);
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
        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');

        return $this->getNumberFormatter()
            ->formatOrdinal($value, $attributes, $textAttributes, $symbols, $locale);
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

    public function isAllowedToRoundPricesAndAmounts(): bool
    {
        return $this->getNumberFormatter()->isAllowedToRoundPricesAndAmounts();
    }

    /**
     * Gets option or default value if option not exist
     *
     * @param array  $options
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getOption(array $options, $name, $default = null)
    {
        return isset($options[$name]) ? $options[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_locale_number';
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
}
