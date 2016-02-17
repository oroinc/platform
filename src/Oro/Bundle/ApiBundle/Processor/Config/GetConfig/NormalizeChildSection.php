<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Copies data from child sections like 'filters' or 'sorters' to root section.
 */
abstract class NormalizeChildSection implements ProcessorInterface
{
    /**
     * @param array  $sectionConfig
     * @param string $sectionName
     * @param array  $definition
     *
     * @return array
     */
    protected function collect(array &$sectionConfig, $sectionName, array $definition)
    {
        $this->updatePropertyPath($sectionConfig, $definition);
        $fields = ConfigUtil::getArrayValue($definition, ConfigUtil::FIELDS);
        foreach ($fields as $fieldName => $fieldConfig) {
            if (is_array($fieldConfig) && !empty($fieldConfig[$sectionName])) {
                $fieldPath = $this->getFieldPropertyPath($fieldConfig) ?: $fieldName;
                $this->collectNested(
                    $sectionConfig,
                    $sectionName,
                    ConfigUtil::getArrayValue($fieldConfig, ConfigUtil::DEFINITION),
                    $fieldConfig[$sectionName],
                    $this->buildPrefix($fieldName),
                    $this->buildPrefix($fieldPath)
                );
            }
        }
    }

    /**
     * @param array  $sectionConfig
     * @param string $sectionName
     * @param array  $definition
     * @param array  $childSectionConfig
     * @param string $fieldPrefix
     * @param string $pathPrefix
     *
     * @return array
     */
    protected function collectNested(
        array &$sectionConfig,
        $sectionName,
        array $definition,
        array $childSectionConfig,
        $fieldPrefix,
        $pathPrefix
    ) {
        $this->updatePropertyPath($childSectionConfig, $definition);
        $fields = ConfigUtil::getArrayValue($childSectionConfig, ConfigUtil::FIELDS);
        foreach ($fields as $fieldName => $config) {
            $fieldPath = ConfigUtil::getPropertyPath($config, $fieldName);

            $field = $fieldPrefix . $fieldName;
            if (!isset($sectionConfig[ConfigUtil::FIELDS][$field])) {
                $path = $pathPrefix . $fieldPath;
                if ($path !== $field) {
                    $config[ConfigUtil::PROPERTY_PATH] = $path;
                } elseif (is_array($config) && array_key_exists(ConfigUtil::PROPERTY_PATH, $config)) {
                    unset($config[ConfigUtil::PROPERTY_PATH]);
                }
                $sectionConfig[ConfigUtil::FIELDS][$field] = $config;
            }

            if (is_array($definition) && !empty($definition[ConfigUtil::FIELDS][$fieldName][$sectionName])) {
                $this->collectNested(
                    $sectionConfig,
                    $sectionName,
                    ConfigUtil::getArrayValue($definition[ConfigUtil::FIELDS][$fieldName], ConfigUtil::DEFINITION),
                    $definition[ConfigUtil::FIELDS][$fieldName][$sectionName],
                    $this->buildPrefix($fieldName, $fieldPrefix),
                    $this->buildPrefix($fieldPath, $pathPrefix)
                );
            }
        }
    }

    /**
     * @param array $sectionConfig
     * @param array $definition
     */
    protected function updatePropertyPath(array &$sectionConfig, array $definition)
    {
        if (!empty($sectionConfig[ConfigUtil::FIELDS])) {
            foreach ($sectionConfig[ConfigUtil::FIELDS] as $fieldName => &$config) {
                if (null === $config || empty($config[ConfigUtil::PROPERTY_PATH])) {
                    $propertyPath = $this->getPropertyPath($definition, $fieldName);
                    if (!empty($propertyPath)) {
                        $config[ConfigUtil::PROPERTY_PATH] = $propertyPath;
                    }
                }
            }
        }
    }

    /**
     * @param array|null $definition
     * @param string     $fieldName
     *
     * @return string|null
     */
    protected function getPropertyPath($definition, $fieldName)
    {
        if (is_array($definition)
            && isset($definition[ConfigUtil::FIELDS][$fieldName])
            && is_array($definition[ConfigUtil::FIELDS][$fieldName])
        ) {
            return $this->getFieldPropertyPath($definition[ConfigUtil::FIELDS][$fieldName]);
        }

        return null;
    }

    /**
     * @param array|null $fieldConfig
     *
     * @return string|null
     */
    protected function getFieldPropertyPath($fieldConfig)
    {
        if (array_key_exists(ConfigUtil::DEFINITION, $fieldConfig)) {
            $fieldConfig = $fieldConfig[ConfigUtil::DEFINITION];
        }

        return is_array($fieldConfig) && !empty($fieldConfig[ConfigUtil::PROPERTY_PATH])
            ? $fieldConfig[ConfigUtil::PROPERTY_PATH]
            : null;
    }

    /**
     * @param string      $field
     * @param string|null $currentPrefix
     *
     * @return string
     */
    protected function buildPrefix($field, $currentPrefix = null)
    {
        return (null !== $currentPrefix ? $currentPrefix . $field : $field) . ConfigUtil::PATH_DELIMITER;
    }
}
