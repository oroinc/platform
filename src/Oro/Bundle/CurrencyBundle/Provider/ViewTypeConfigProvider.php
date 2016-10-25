<?php


namespace Oro\Bundle\CurrencyBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ViewTypeConfigProvider implements ViewTypeProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /** {@inheritdoc} */
    public function getViewType()
    {
        return $this->configManager->get('oro_currency.currency_display');
    }
}
