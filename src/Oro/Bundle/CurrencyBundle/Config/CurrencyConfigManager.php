<?php

namespace Oro\Bundle\CurrencyBundle\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;

class CurrencyConfigManager implements CurrencyConfigInterface, CurrencyProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * CurrencyConfigManager constructor.
     *
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCurrency()
    {
        return $this->configManager->get(CurrencyConfig::getConfigKeyByName(
            CurrencyConfig::KEY_DEFAULT_CURRENCY
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getViewType()
    {
        return $this->configManager->get(CurrencyConfig::getConfigKeyByName(
            CurrencyConfig::KEY_CURRENCY_DISPLAY
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencyList()
    {
        return (array) $this->getDefaultCurrency();
    }
}
