<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Class ExtendEntityConfigProvider corresponds for returning configs for extend entities
 * (or only entities with attributes if needed)
 */
class ExtendEntityConfigProvider
{
    /** @var  ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param bool $attributesOnly
     * @param null|callable $filter
     * @return ConfigInterface[]
     */
    public function getExtendEntityConfigs($attributesOnly = false, $filter = null)
    {
        $configsToReturn = [];
        $attributeProvider = $this->configManager->getProvider('attribute');
        $extendProvider = $this->configManager->getProvider('extend');

        $extendConfigs = $filter ?
            $extendProvider->filter($filter, null, true) :
            $extendProvider->getConfigs(null, true);


        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->is('is_extend')) {
                $className = $extendConfig->getId()->getClassName();
                if ($attributesOnly && !$attributeProvider->getConfig($className)->is('has_attributes')) {
                    continue;
                }

                $configsToReturn[] = $extendConfig;
            }
        }
        return $configsToReturn;
    }
}
