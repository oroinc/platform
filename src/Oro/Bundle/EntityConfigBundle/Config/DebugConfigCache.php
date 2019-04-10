<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

/**
 * Outdated class that is kept to avoid BC break.
 * @deprecated will be removed in v4.0
 */
class DebugConfigCache extends ConfigCache
{
    /**
     * {@inheritdoc}
     */
    public function saveConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        return parent::saveConfig($config, $localCacheOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function saveFieldConfigValues(array $values, $className, $fieldName, $fieldType)
    {
        return parent::saveFieldConfigValues($values, $className, $fieldName, $fieldType);
    }
}
