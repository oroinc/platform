<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

abstract class NormalizeChildSection implements ProcessorInterface
{
    /**
     * @param array       $sectionConfig
     * @param string      $sectionName
     * @param array       $definition
     * @param string|null $fieldPrefix
     * @param string|null $pathPrefix
     *
     * @return array
     */
    protected function collect(
        array &$sectionConfig,
        $sectionName,
        array $definition,
        $fieldPrefix = null,
        $pathPrefix = null
    ) {
        $fields = ConfigUtil::getArrayValue($definition, ConfigUtil::FIELDS);
        foreach ($fields as $fieldName => $fieldConfig) {
            $fieldPath = !empty($fieldConfig[ConfigUtil::PROPERTY_PATH])
                ? $fieldConfig[ConfigUtil::PROPERTY_PATH]
                : $fieldName;
            if (null !== $fieldPrefix) {
                $field = $fieldPrefix . $fieldName;
                if (!isset($sectionConfig[ConfigUtil::FIELDS][$field])) {
                    $path = $pathPrefix . $fieldPath;
                    if ($path !== $field) {
                        $fieldConfig[ConfigUtil::PROPERTY_PATH] = $path;
                    } elseif (array_key_exists(ConfigUtil::PROPERTY_PATH, $fieldConfig)) {
                        unset($fieldConfig[ConfigUtil::PROPERTY_PATH]);
                    }
                    $sectionConfig[ConfigUtil::FIELDS][$field] = $fieldConfig;
                }
            }
            if (!empty($fieldConfig[$sectionName])) {
                $this->collect(
                    $sectionConfig,
                    $sectionName,
                    $fieldConfig[$sectionName],
                    $this->buildPrefix($fieldName, $fieldPrefix),
                    $this->buildPrefix($fieldPath, $pathPrefix)
                );
            }
        }
    }

    /**
     * @param string      $field
     * @param string|null $currentPrefix
     *
     * @return string
     */
    protected function buildPrefix($field, $currentPrefix)
    {
        return (null !== $currentPrefix ? $currentPrefix . $field : $field) . ConfigUtil::PATH_DELIMITER;
    }
}
