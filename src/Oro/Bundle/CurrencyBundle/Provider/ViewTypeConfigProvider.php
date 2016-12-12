<?php


namespace Oro\Bundle\CurrencyBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;

class ViewTypeConfigProvider implements ViewTypeProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /** {@inheritdoc} */
    public function getViewType()
    {
        return $this->configManager->get(CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_CURRENCY_DISPLAY));
    }
}
