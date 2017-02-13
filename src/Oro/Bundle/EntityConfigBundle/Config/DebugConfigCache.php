<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class DebugConfigCache extends ConfigCache
{
    /**
     * {@inheritdoc}
     */
    public function saveConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        $configId = $config->getId();
        if ($configId instanceof FieldConfigId && null === $configId->getFieldType()) {
            // undefined field type can cause unpredictable logical bugs
            throw new \InvalidArgumentException(
                sprintf(
                    'A field config "%s::%s" with undefined field type cannot be cached.'
                    . ' It seems that there is some critical bug in entity config core functionality.'
                    . ' Please contact ORO team if you see this error.',
                    $configId->getClassName(),
                    $configId->getFieldName()
                )
            );
        }

        return parent::saveConfig($config, $localCacheOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function saveFieldConfigValues(array $values, $className, $fieldName, $fieldType)
    {
        if (!$fieldType) {
            // undefined field type can cause unpredictable logical bugs
            throw new \InvalidArgumentException(
                sprintf(
                    'A field config "%s::%s" with undefined field type cannot be cached.'
                    . ' It seems that there is some critical bug in entity config core functionality.'
                    . ' Please contact ORO team if you see this error.',
                    $className,
                    $fieldName
                )
            );
        }

        return parent::saveFieldConfigValues($values, $className, $fieldName, $fieldType);
    }
}
