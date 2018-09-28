<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Class ExtendEntityConfigProvider corresponds for returning configs for extend entities
 */
class ExtendEntityConfigProvider implements ExtendEntityConfigProviderInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

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
    public function getExtendEntityConfigs($filter = null)
    {
        $configsToReturn = [];
        $extendProvider = $this->configManager->getProvider('extend');

        $extendConfigs = $filter ?
            $extendProvider->filter($filter, null, true) :
            $extendProvider->getConfigs(null, true);

        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->is('is_extend')) {
                $configsToReturn[] = $extendConfig;
            }
        }

        return $configsToReturn;
    }
}
