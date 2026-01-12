<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;

/**
 * Provides currency display format configuration from system settings.
 *
 * This provider retrieves the configured currency display format (symbol, ISO code,
 * name, or full name) from the system configuration. It implements caching to avoid
 * repeated configuration lookups and ensures consistent currency presentation across
 * the application based on the administrator's preferences.
 */
class ViewTypeConfigProvider implements ViewTypeProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var string
     */
    protected $viewType;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function getViewType()
    {
        if (!$this->viewType) {
            $this->viewType = $this->configManager
                ->get(CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_CURRENCY_DISPLAY));
        }

        return $this->viewType;
    }
}
