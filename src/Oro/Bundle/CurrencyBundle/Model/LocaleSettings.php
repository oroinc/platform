<?php

namespace Oro\Bundle\CurrencyBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\CalendarFactoryInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;
use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager;

class LocaleSettings extends BaseLocaleSettings
{
    protected $currencyConfigManager;

    /**
     * LocaleSettings constructor.
     *
     * @param ConfigManager            $configManager
     * @param CalendarFactoryInterface $calendarFactory
     * @param CurrencyConfigManager    $currencyConfigManager
     */
    public function __construct(
        ConfigManager $configManager,
        CalendarFactoryInterface $calendarFactory,
        CurrencyConfigManager $currencyConfigManager
    ) {
        parent::__construct($configManager, $calendarFactory);
        $this->currencyConfigManager = $currencyConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencySymbolByCurrency($currencyCode = null)
    {
        if ($this->currencyConfigManager->getViewType() === CurrencyConfigManager::VIEW_TYPE_ISO_CODE) {
            return $currencyCode;
        }

        return parent::getCurrencySymbolByCurrency($currencyCode);
    }
}
