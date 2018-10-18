<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Used to call number formatter to format numbers, currencies, percents etc
 * according to locale and additional parameters
 */
class NumberExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
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
            new \Twig_SimpleFunction(
                'oro_locale_number_attribute',
                [$this, 'getAttribute'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_locale_number_text_attribute',
                [$this, 'getTextAttribute'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_locale_number_symbol',
                [$this, 'getSymbol'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_currency_symbol_prepend',
                [$this, 'isCurrencySymbolPrepend'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_format_number',
                [$this, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_currency',
                [$this, 'formatCurrency'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_decimal',
                [$this, 'formatDecimal'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_percent',
                [$this, 'formatPercent'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_spellout',
                [$this, 'formatSpellout'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_duration',
                [$this, 'formatDuration'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_ordinal',
                [$this, 'formatOrdinal'],
                ['is_safe' => ['html']]
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
     * @return int
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
     * @return string
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
     * @return string
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

        return $this->getNumberFormatter()
            ->formatCurrency($value, $currency, $attributes, $textAttributes, $symbols, $locale);
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
}
