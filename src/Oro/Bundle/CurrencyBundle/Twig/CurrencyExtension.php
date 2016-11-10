<?php

namespace Oro\Bundle\CurrencyBundle\Twig;

use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class CurrencyExtension extends \Twig_Extension
{
    /**
     * @var NumberFormatter
     */
    protected $formatter;

    /**
     * @var ViewTypeProviderInterface
     */
    protected $provider;

    /**
     * @var CurrencyNameHelper
     */
    protected $currencyNameHelper;

    /**
     * @param NumberFormatter           $formatter
     * @param ViewTypeProviderInterface $provider
     */
    public function __construct(
        NumberFormatter $formatter,
        ViewTypeProviderInterface $provider,
        CurrencyNameHelper $currencyNameHelper
    ) {
        $this->formatter = $formatter;
        $this->provider  = $provider;
        $this->currencyNameHelper = $currencyNameHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_currency_view_type', array($this->provider, 'getViewType')),
            new \Twig_SimpleFunction(
                'oro_currency_symbol_collection',
                [$this, 'getSymbolCollection'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_format_price',
                [$this, 'formatPrice'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_localized_currency_name',
                [Intl::getCurrencyBundle(), 'getCurrencyName']
            )
        ];
    }

    /**
     * Formats currency number according to locale settings.
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
     * @param Price $price
     * @param array $options
     * @return string
     */
    public function formatPrice(Price $price, array $options = [])
    {
        $value = $price->getValue();
        $currency = $price->getCurrency();

        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');

        return $this->formatter->formatCurrency($value, $currency, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Returns symbols for active currencies
     *
     * @return string json object with active currency symbols
     */
    public function getSymbolCollection()
    {
        $currencySymbolCollection = $this->currencyNameHelper->getCurrencyChoices(
            ViewTypeProviderInterface::VIEW_TYPE_SYMBOL
        );
        
        $currencySymbolCollection = array_map(function ($symbol) {
            return ['symbol' => $symbol];
        }, $currencySymbolCollection);

        return json_encode($currencySymbolCollection);
    }

    /**
     * Gets option or default value if option not exist
     *
     * @param array $options
     * @param string $name
     * @param mixed $default
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
        return 'oro_currency';
    }
}
