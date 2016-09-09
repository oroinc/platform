<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Exception\InvalidFallbackProviderArgumentException;

class SystemConfigFallbackProvider extends AbstractEntityFallbackProvider
{
    const CONFIG_NAME_KEY = 'configName';
    const FALLBACK_ID = 'systemConfig';

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

    /**
     * {@inheritdoc}
     */
    public function getFallbackHolderEntity(
        $object,
        $objectFieldName
    ) {
        $fallbackConfig = $this->getEntityConfig($object, $objectFieldName)[self::FALLBACK_ID];
        if (!array_key_exists(self::CONFIG_NAME_KEY, $fallbackConfig)) {
            throw new InvalidFallbackProviderArgumentException(
                sprintf(
                    "You must define the '%s' fallback option for entity '%s' field '%s', fallback id '%s'",
                    self::CONFIG_NAME_KEY,
                    get_class($object),
                    $objectFieldName,
                    self::FALLBACK_ID
                )
            );
        }

        $configName = $fallbackConfig[self::CONFIG_NAME_KEY];

        return $this->configManager->get($configName);
    }
}
