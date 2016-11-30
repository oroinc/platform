<?php

namespace Oro\Bundle\CurrencyBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Config\CurrenciesViewTypeAwareInterface;
use Oro\Bundle\LocaleBundle\Model\CalendarFactoryInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;

class LocaleSettings extends BaseLocaleSettings
{
    /**
     * @var CurrenciesViewTypeAwareInterface
     */
    protected $viewTypeAware;

    /**
     * LocaleSettings constructor.
     * @param ConfigManager $configManager
     * @param CalendarFactoryInterface $calendarFactory
     * @param CurrenciesViewTypeAwareInterface $viewTypeAware
     */
    public function __construct(
        ConfigManager $configManager,
        CalendarFactoryInterface $calendarFactory,
        CurrenciesViewTypeAwareInterface $viewTypeAware
    ) {
        parent::__construct($configManager, $calendarFactory);
        $this->viewTypeAware = $viewTypeAware;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencySymbolByCurrency($currencyCode = null)
    {
        if ($this->viewTypeAware->getViewType() === CurrenciesViewTypeAwareInterface::VIEW_TYPE_ISO_CODE) {
            return $currencyCode;
        }

        return parent::getCurrencySymbolByCurrency($currencyCode);
    }
}
