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
     * @var string
     */
    protected $viewType;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewType()
    {
        if (!$this->viewType) {
            $this->viewType = $this->configManager
                ->get(CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_CURRENCY_DISPLAY));
        }

        return $this->viewType;
    }
}
