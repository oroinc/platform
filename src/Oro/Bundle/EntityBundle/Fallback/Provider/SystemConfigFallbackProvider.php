<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\InvalidProviderArgumentException;

class SystemConfigFallbackProvider extends AbstractEntityFallbackProvider
{
    const CONFIG_NAME_KEY = 'configName';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * SystemConfigFallbackProvider constructor.
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
    public function getFallbackHolderEntity(
        $object,
        $objectFieldName,
        EntityFieldFallbackValue $objectFallbackValue,
        $fallbackConfig
    ) {
        if (!array_key_exists(self::CONFIG_NAME_KEY, $fallbackConfig)) {
            throw new InvalidProviderArgumentException(
                sprintf(
                    "You must define the '%s' fallback option for entity '%s' field '%s'",
                    self::CONFIG_NAME_KEY,
                    get_class($object),
                    $objectFieldName
                )
            );
        }

        $configName = $fallbackConfig[self::CONFIG_NAME_KEY];

        return $this->configManager->get($configName);
    }
}
