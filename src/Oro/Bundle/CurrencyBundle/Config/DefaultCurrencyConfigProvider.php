<?php

namespace Oro\Bundle\CurrencyBundle\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;

/**
 * Provides default currency configuration from the system configuration.
 *
 * This provider retrieves the default currency setting from the application's
 * configuration manager and implements the {@see CurrencyProviderInterface} to supply
 * currency information to other components.
 */
class DefaultCurrencyConfigProvider implements CurrencyProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * CurrencyConfigManager constructor.
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function getDefaultCurrency()
    {
        return $this->configManager->get(CurrencyConfig::getConfigKeyByName(
            CurrencyConfig::KEY_DEFAULT_CURRENCY
        ));
    }

    #[\Override]
    public function getCurrencyList()
    {
        return (array) $this->getDefaultCurrency();
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        $currencies = $this->getCurrencyList();
        return array_combine($currencies, $currencies);
    }
}
