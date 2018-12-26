<?php

namespace Oro\Component\EntitySerializer;

/**
 * Prepares a configuration to be used by EntitySerializer.
 */
class ConfigNormalizer
{
    /**
     * Prepares a configuration to be used by EntitySerializer
     *
     * @param array $config
     *
     * @return array
     */
    public function normalizeConfig(array $config)
    {
        $config = $this->preNormalizeConfig($config);

        return $this->doNormalizeConfig($config);
    }

    /**
     * Remembers the current config state before it will be normalized
     *
     * @param array $config
     *
     * @return array
     */
    protected function preNormalizeConfig(array $config)
    {
        if (\array_key_exists(ConfigUtil::FIELDS, $config)) {
            if (\is_string($config[ConfigUtil::FIELDS])) {
                // expands 'fields' => 'field_name' to its full definition
                $this->applySingleFieldConfig($config);
            } elseif (!empty($config[ConfigUtil::FIELDS])) {
                // remember the field name of the collapsed association because
                // additional fields can be added during config normalization
                // it is required to return a correct representation of serialized data
                if (ConfigUtil::isCollapse($config)) {
                    $collapseField = $this->getCollapseField($config);
                    if ($collapseField) {
                        $config[ConfigUtil::COLLAPSE_FIELD] = $collapseField;
                    }
                }

                $excludedFields = [];
                $fields = \array_keys($config[ConfigUtil::FIELDS]);
                foreach ($fields as $field) {
                    $fieldConfig = $config[ConfigUtil::FIELDS][$field];
                    if (null !== $fieldConfig) {
                        if (ConfigUtil::isExclude($fieldConfig)) {
                            $excludedFields[] = $field;
                        }
                        $config[ConfigUtil::FIELDS][$field] = $this->preNormalizeConfig($fieldConfig);
                    }
                }
                // remember the list of excluded fields, because the 'exclude' option
                // can be removed during config normalization
                // it is required to return a correct representation of serialized data
                if (!empty($excludedFields)) {
                    $config[ConfigUtil::EXCLUDED_FIELDS] = $excludedFields;
                }
            }
        }

        return $config;
    }

    /**
     * Performs the normalization of a configuration
     *
     * @param array $config
     *
     * @return array
     */
    protected function doNormalizeConfig(array $config)
    {
        if (\array_key_exists(ConfigUtil::FIELDS, $config) && !empty($config[ConfigUtil::FIELDS])) {
            $renamedFields = [];
            $fields = \array_keys($config[ConfigUtil::FIELDS]);
            foreach ($fields as $field) {
                $fieldConfig = $config[ConfigUtil::FIELDS][$field];
                if (null !== $fieldConfig) {
                    if (isset($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
                        $propertyPath = $fieldConfig[ConfigUtil::PROPERTY_PATH];
                        if ($propertyPath) {
                            $path = ConfigUtil::explodePropertyPath($propertyPath);
                            if (\count($path) === 1) {
                                $renamedFields[$propertyPath] = $field;
                            } else {
                                $config = $this->applyPropertyPathConfig($config, $path);
                            }
                        } else {
                            unset($config[ConfigUtil::FIELDS][$field][ConfigUtil::PROPERTY_PATH]);
                        }
                    }
                    $config[ConfigUtil::FIELDS][$field] = $this->doNormalizeConfig($fieldConfig);
                }
            }
            // remember the map of renamed fields to speed up the serialization
            if (!empty($renamedFields)) {
                $config[ConfigUtil::RENAMED_FIELDS] = $renamedFields;
            }
        }

        return $config;
    }

    /**
     * Checks that all fields from the given property path exist in the config
     * and add them if needed
     *
     * @param array    $config
     * @param string[] $propertyPath
     *
     * @return array
     */
    protected function applyPropertyPathConfig(array $config, array $propertyPath)
    {
        // set a reference to the root config
        $currentConfig = &$config;

        foreach ($propertyPath as $property) {
            if (empty($currentConfig[ConfigUtil::FIELDS])) {
                $currentConfig[ConfigUtil::FIELDS] = [$property => null];
            } else {
                $field = $this->findField($currentConfig[ConfigUtil::FIELDS], $property);
                if ($field) {
                    $property = $field;
                    // remove 'exclude' option if it is TRUE, because there is another field
                    // that uses data from this association and as result the association should be loaded
                    if (\is_array($currentConfig[ConfigUtil::FIELDS][$property])
                        && ConfigUtil::isExclude($currentConfig[ConfigUtil::FIELDS][$property])
                    ) {
                        unset($currentConfig[ConfigUtil::FIELDS][$property][ConfigUtil::EXCLUDE]);
                        // set 'exclusion_policy'='all' if it not defined yet,
                        // it is required to prevent loading of not used fields
                        if (!isset($currentConfig[ConfigUtil::FIELDS][$property][ConfigUtil::EXCLUSION_POLICY])) {
                            $currentConfig[ConfigUtil::FIELDS][$property][ConfigUtil::EXCLUSION_POLICY] =
                                ConfigUtil::EXCLUSION_POLICY_ALL;
                        }
                    }
                } else {
                    $currentConfig[ConfigUtil::FIELDS][$property] = null;
                }
            }

            // set a reference to the next child config
            $currentConfig = &$currentConfig[ConfigUtil::FIELDS][$property];
        }

        return $config;
    }

    /**
     * Expands simplified definition of collapsed association to its full definition
     *
     * @param array $config
     */
    protected function applySingleFieldConfig(array &$config)
    {
        $field = $config[ConfigUtil::FIELDS];

        $config[ConfigUtil::EXCLUSION_POLICY] = ConfigUtil::EXCLUSION_POLICY_ALL;
        $config[ConfigUtil::COLLAPSE] = true;
        $config[ConfigUtil::COLLAPSE_FIELD] = $field;
        $config[ConfigUtil::FIELDS] = [$field => null];
    }

    /**
     * Finds a field by its property path or name
     *
     * @param array  $fields
     * @param string $property
     *
     * @return string|null The name of the found field; otherwise, NULL
     */
    protected function findField(array $fields, $property)
    {
        // at the first try to find a field by the property path,
        // it is a case when a field was renamed
        $field = $this->findFieldByPropertyPath($fields, $property);
        // if a renamed field was not found, check if a field with the specifies name exists in the config
        if (!$field && array_key_exists($property, $fields)) {
            $field = $property;
        }

        return $field;
    }

    /**
     * Finds a field by its property path
     *
     * @param array  $fields
     * @param string $property
     *
     * @return string|null The name of the found field; otherwise, NULL
     */
    protected function findFieldByPropertyPath(array $fields, $property)
    {
        foreach ($fields as $fieldName => $fieldConfig) {
            if (\is_array($fieldConfig) && isset($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
                if ($fieldConfig[ConfigUtil::PROPERTY_PATH] === $property) {
                    return $fieldName;
                }
            } elseif ($fieldName === $property) {
                return $fieldName;
            }
        }

        return null;
    }

    /**
     * @param array $config
     *
     * @return string|null
     */
    protected function getCollapseField(array $config)
    {
        $result = null;
        foreach ($config[ConfigUtil::FIELDS] as $field => $fieldConfig) {
            if (!\is_array($fieldConfig) || !ConfigUtil::isExclude($fieldConfig)) {
                if ($result) {
                    $result = null;
                    break;
                }
                $result = $field;
            }
        }

        return $result;
    }
}
