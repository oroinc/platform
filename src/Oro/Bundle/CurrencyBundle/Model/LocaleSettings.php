<?php

namespace Oro\Bundle\CurrencyBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\LocaleBundle\Model\CalendarFactoryInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;

/**
 * LocaleSettings specific for CurrencyBundle:
 * - overrides ::getCurrencySymbolByCurrency() to make it returns currency ISO code if current view type is `iso_code`
 * or currency is not enabled.
 */
class LocaleSettings extends BaseLocaleSettings
{
    /**
     * @var ViewTypeProviderInterface
     */
    protected $viewTypeProvider;

    /**
     * @var CurrencyProviderInterface
     */
    protected $currencyProvider;

    /**
     * @param ConfigManager $configManager
     * @param CalendarFactoryInterface $calendarFactory
     * @param ViewTypeProviderInterface $viewTypeProvider
     * @param CurrencyProviderInterface $currencyProvider
     */
    public function __construct(
        ConfigManager $configManager,
        CalendarFactoryInterface $calendarFactory,
        ViewTypeProviderInterface $viewTypeProvider,
        CurrencyProviderInterface $currencyProvider
    ) {
        parent::__construct($configManager, $calendarFactory);
        $this->viewTypeProvider = $viewTypeProvider;
        $this->currencyProvider = $currencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencySymbolByCurrency(string $currencyCode = null, string $locale = null): string
    {
        // Returns currency ISO code when view type is `iso_code` or currency is not enabled.
        if ($this->viewTypeProvider->getViewType() === ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE
            || !\in_array($currencyCode, $this->currencyProvider->getCurrencyList())) {
            return $currencyCode;
        }

        return parent::getCurrencySymbolByCurrency($currencyCode, $locale);
    }
}
