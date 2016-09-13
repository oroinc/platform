<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackProviderArgumentException;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;

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
        $fallbackConfig = $this->getEntityConfig(
            $object,
            $objectFieldName
        );
        $systemConfig = $fallbackConfig[EntityFieldFallbackValue::FALLBACK_LIST_KEY][self::FALLBACK_ID];
        if (!array_key_exists(self::CONFIG_NAME_KEY, $systemConfig)) {
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

        return $this->configManager->get($systemConfig[self::CONFIG_NAME_KEY]);
    }
}
