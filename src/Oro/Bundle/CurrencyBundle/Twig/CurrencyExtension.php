<?php

namespace Oro\Bundle\CurrencyBundle\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Psr\Container\ContainerInterface;
use Symfony\Component\Intl\Currencies;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve currency display configuration:
 *   - oro_currency_view_type
 *   - oro_currency_symbol_collection
 *
 * Provides Twig filter to format prices:
 *   - oro_format_price
 *   - oro_localized_currency_name
 */
class CurrencyExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_currency_view_type', [$this, 'getViewType']),
            new TwigFunction(
                'oro_currency_symbol_collection',
                [$this, 'getSymbolCollection'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('oro_format_price', [$this, 'formatPrice'], ['is_safe' => ['html']]),
            new TwigFilter('oro_localized_currency_name', [$this, 'getCurrencyName'])
        ];
    }

    /**
     * @return string
     */
    public function getViewType()
    {
        return $this->getViewTypeProvider()->getViewType();
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
        $formattedValue = $this->getNumberFormatter()->formatCurrency(
            $price->getValue(),
            $price->getCurrency(),
            (array)($options['attributes'] ?? []),
            (array)($options['textAttributes'] ?? []),
            (array)($options['symbols'] ?? []),
            $options['locale'] ?? null
        );

        return strip_tags($formattedValue);
    }

    /**
     * Returns symbols for active currencies
     *
     * @return array Collection of active currency codes and symbols
     */
    public function getSymbolCollection()
    {
        $currencySymbolCollection = $this->getCurrencyNameHelper()->getCurrencyChoices(
            ViewTypeProviderInterface::VIEW_TYPE_SYMBOL
        );

        return array_map(
            function ($symbol) {
                return ['symbol' => $symbol];
            },
            array_flip($currencySymbolCollection)
        );
    }

    /**
     * @param string      $currency
     * @param string|null $displayLocale
     *
     * @return string|null
     */
    public function getCurrencyName($currency, $displayLocale = null)
    {
        return Currencies::getName($currency, $displayLocale);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_currency.provider.view_type' => ViewTypeProviderInterface::class,
            CurrencyNameHelper::class,
            NumberFormatter::class
        ];
    }

    private function getViewTypeProvider(): ViewTypeProviderInterface
    {
        return $this->container->get('oro_currency.provider.view_type');
    }

    private function getCurrencyNameHelper(): CurrencyNameHelper
    {
        return $this->container->get(CurrencyNameHelper::class);
    }

    private function getNumberFormatter(): NumberFormatter
    {
        return $this->container->get(NumberFormatter::class);
    }
}
