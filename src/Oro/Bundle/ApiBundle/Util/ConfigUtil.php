<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\EntitySerializer\ConfigUtil as BaseConfigUtil;

class ConfigUtil extends BaseConfigUtil
{
    const DEFINITION = 'definition';
    const FILTERS    = 'filters';
    const SORTERS    = 'sorters';

    /**
     * a human-readable representation of an object like entity, field, filter, etc.
     * can be a string or Label object.
     */
    const LABEL = 'label';

    /**
     * a human-readable representation in plural of an entity.
     * can be a string or Label object.
     */
    const PLURAL_LABEL = 'plural_label';

    /**
     * a human-readable description of an object like entity, field, filter, etc.
     * can be a string or Label object.
     */
    const DESCRIPTION = 'description';

    const DATA_TYPE     = 'data_type';
    const DEFAULT_VALUE = 'default_value';
    const ALLOW_ARRAY   = 'allow_array';
    const INHERIT       = 'inherit';

    /**
     * @return array
     */
    public static function getInitialConfig()
    {
        return [
            self::EXCLUSION_POLICY => self::EXCLUSION_POLICY_NONE,
            self::FIELDS           => []
        ];
    }

    /**
     * @param array $config
     *
     * @return bool
     */
    public static function isInherit(array $config)
    {
        return
            !isset($config[self::INHERIT])
            || $config[self::INHERIT];
    }

    /**
     * @param array $config
     *
     * @return string
     */
    public static function isRelationInitialized(array $config)
    {
        return
            isset($config[self::FIELDS])
            || (
                isset($config[self::EXCLUSION_POLICY])
                && $config[self::EXCLUSION_POLICY] !== self::EXCLUSION_POLICY_ALL
            );
    }

    /**
     * Removes all fields marked with 'exclude' attribute.
     *
     * @param array $fields
     *
     * @return array
     */
    public static function removeExclusions(array $fields)
    {
        return array_filter(
            $fields,
            function ($config) {
                return !is_array($config) || !self::isExclude($config);
            }
        );
    }

    /**
     * Checks whether an entity has the given field and it is not marked with 'exclude' attribute.
     *
     * @param array  $config The config of an entity
     * @param string $field  The name of the field
     *
     * @return bool
     */
    public static function isExcludedField(array $config, $field)
    {
        $result = false;
        if (isset($config[ConfigUtil::FIELDS])) {
            $fields = $config[ConfigUtil::FIELDS];
            if (!array_key_exists($field, $fields)) {
                $result = true;
            } else {
                $fieldConfig = $fields[$field];
                if (is_array($fieldConfig)) {
                    if (array_key_exists(ConfigUtil::DEFINITION, $fieldConfig)) {
                        $fieldConfig = $fieldConfig[ConfigUtil::DEFINITION];
                    }
                    if (is_array($fieldConfig) && ConfigUtil::isExclude($fieldConfig)) {
                        $result = true;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns the property path to the field.
     *
     * @param array|null $fieldConfig
     * @param string     $fieldName
     *
     * @return string
     */
    public static function getPropertyPath($fieldConfig, $fieldName)
    {
        return !empty($fieldConfig[ConfigUtil::PROPERTY_PATH])
            ? $fieldConfig[ConfigUtil::PROPERTY_PATH]
            : $fieldName;
    }
}
