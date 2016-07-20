<?php

namespace Oro\Component\EntitySerializer;

class ConfigNormalizer
{
    /**
     * Normalizes a configuration of the EntitySerializer
     *
     * @param array       $config
     * @param string|null $parentField
     *
     * @return array
     */
    public function normalizeConfig(array $config, $parentField = null)
    {
        // @deprecated since 1.9. Use 'exclude' attribute for a field instead of 'excluded_fields' for an entity
        if (array_key_exists('excluded_fields', $config)) {
            $config = $this->applyExcludedFieldsConfig($config);
        }

        // @deprecated since 1.9. Use 'order_by' attribute instead of 'orderBy'
        if (array_key_exists('orderBy', $config)) {
            $config[ConfigUtil::ORDER_BY] = $config['orderBy'];
            unset($config['orderBy']);
        }

        if (array_key_exists(ConfigUtil::FIELDS, $config)) {
            if (is_string($config[ConfigUtil::FIELDS])) {
                $this->applySingleFieldConfig($config, $parentField);
            } elseif (!empty($config[ConfigUtil::FIELDS])) {
                $fields = array_keys($config[ConfigUtil::FIELDS]);
                foreach ($fields as $field) {
                    $fieldConfig = $config[ConfigUtil::FIELDS][$field];
                    if (null !== $fieldConfig) {
                        // @deprecated since 1.9. Use `property_path` attribute instead of 'result_name'
                        if (isset($fieldConfig['result_name'])) {
                            $field = $this->applyResultNameConfig($config, $fieldConfig, $field);
                        }

                        if (isset($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
                            if (isset($fieldConfig[ConfigUtil::FIELDS])) {
                                $config[ConfigUtil::FIELDS][$field] = $this->applyPropertyPathConfig(
                                    $fieldConfig,
                                    $fieldConfig[ConfigUtil::PROPERTY_PATH],
                                    $fieldConfig,
                                    $parentField
                                );
                            } else {
                                $config = $this->applyPropertyPathConfig(
                                    $config,
                                    $fieldConfig[ConfigUtil::PROPERTY_PATH],
                                    $fieldConfig,
                                    $parentField
                                );
                            }
                        } elseif ($this->isCollapsedWithoutPropertyPath($fieldConfig)) {
                            $targetFields = array_keys($fieldConfig[ConfigUtil::FIELDS]);
                            $fieldConfig[ConfigUtil::PROPERTY_PATH] = $field . '.' . reset($targetFields);
                        }

                        $config[ConfigUtil::FIELDS][$field] = $this->normalizeConfig($fieldConfig, $field);
                    }
                }
            }
        }

        return $config;
    }

    /**
     * @param array $config
     *
     * @return array
     *
     * @deprecated since 1.9. Use 'exclude' attribute for a field instead of 'excluded_fields' for an entity
     */
    protected function applyExcludedFieldsConfig(array $config)
    {
        $excludedFields = ConfigUtil::getArrayValue($config, 'excluded_fields');
        unset($config['excluded_fields']);
        foreach ($excludedFields as $field) {
            $fieldConfig = ConfigUtil::getFieldConfig($config, $field);
            if (!ConfigUtil::isExclude($fieldConfig)) {
                $fieldConfig[ConfigUtil::EXCLUDE]   = true;
                $config[ConfigUtil::FIELDS][$field] = $fieldConfig;
            }
        }

        return $config;
    }

    /**
     * @param array  $config
     * @param array  $fieldConfig
     * @param string $field
     *
     * @return mixed
     *
     * @deprecated since 1.9. Use `property_path` attribute instead of 'result_name'
     */
    protected function applyResultNameConfig(array &$config, array &$fieldConfig, $field)
    {
        $fieldConfig[ConfigUtil::PROPERTY_PATH] = $field;
        unset($config[ConfigUtil::FIELDS][$field]);

        $field = $fieldConfig['result_name'];
        unset($fieldConfig['result_name']);

        return $field;
    }

    /**
     * @param array  $config
     * @param string $propertyPath
     * @param array  $fieldConfig
     * @param string $parentField
     *
     * @return array
     */
    protected function applyPropertyPathConfig(array $config, $propertyPath, array &$fieldConfig, $parentField)
    {
        $properties = ConfigUtil::explodePropertyPath($propertyPath);

        $currentConfig   = &$config;
        $currentProperty = $properties[0];

        $this->applyPropertyConfig($currentConfig, $currentProperty, $parentField);

        $count = count($properties);
        if ($count > 1) {
            $i = 1;
            while ($i < $count) {
                $currentConfig = &$currentConfig[ConfigUtil::FIELDS][$properties[$i - 1]];
                if (null === $currentConfig) {
                    $currentConfig = [];
                }
                $currentProperty = $properties[$i];
                $this->applyPropertyConfig($currentConfig, $currentProperty, $properties[$i - 1]);
                $i++;
            }
        }

        if (array_key_exists(ConfigUtil::DATA_TRANSFORMER, $fieldConfig)) {
            $currentConfig[ConfigUtil::FIELDS][$currentProperty][ConfigUtil::DATA_TRANSFORMER] =
                $fieldConfig[ConfigUtil::DATA_TRANSFORMER];
            unset($fieldConfig[ConfigUtil::DATA_TRANSFORMER]);
        }

        return $config;
    }

    /**
     * @param array  $config
     * @param string $property
     * @param string $parentField
     */
    protected function applyPropertyConfig(array &$config, $property, $parentField)
    {
        if (!isset($config[ConfigUtil::FIELDS])) {
            $config[ConfigUtil::FIELDS][$property] = null;
            if (isset($config[ConfigUtil::EXCLUDE]) && $config[ConfigUtil::EXCLUDE]) {
                $config[ConfigUtil::EXCLUSION_POLICY] = ConfigUtil::EXCLUSION_POLICY_ALL;
                unset($config[ConfigUtil::EXCLUDE]);
            }
        } elseif (is_string($config[ConfigUtil::FIELDS])) {
            $this->applySingleFieldConfig($config, $parentField);
            $config[ConfigUtil::FIELDS][$property] = null;
        } elseif (!array_key_exists($property, $config[ConfigUtil::FIELDS])) {
            $config[ConfigUtil::FIELDS][$property] = null;
        }
    }

    /**
     * @param array  $config
     * @param string $parentField
     */
    protected function applySingleFieldConfig(array &$config, $parentField)
    {
        $field = $config[ConfigUtil::FIELDS];

        $config[ConfigUtil::COLLAPSE]         = true;
        $config[ConfigUtil::EXCLUSION_POLICY] = ConfigUtil::EXCLUSION_POLICY_ALL;
        $config[ConfigUtil::PROPERTY_PATH]    = $parentField ? $parentField . '.' . $field : $field;
        $config[ConfigUtil::FIELDS]           = [$field => null];
    }

    /**
     * @param array $fieldConfig
     *
     * @return bool
     */
    protected function isCollapsedWithoutPropertyPath(array $fieldConfig)
    {
        return
            isset($fieldConfig[ConfigUtil::COLLAPSE])
            && $fieldConfig[ConfigUtil::COLLAPSE]
            && ConfigUtil::isExcludeAll($fieldConfig)
            && isset($fieldConfig[ConfigUtil::FIELDS])
            && is_array($fieldConfig[ConfigUtil::FIELDS])
            && count($fieldConfig[ConfigUtil::FIELDS]) === 1;
    }
}
