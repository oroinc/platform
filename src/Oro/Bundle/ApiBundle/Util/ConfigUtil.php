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
