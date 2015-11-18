<?php

namespace Oro\Bundle\ApiBundle\Util;

final class ConfigUtil
{
    const DEFINITION = 'definition';
    const FILTERS    = 'filters';
    const SORTERS    = 'sorters';

    const EXCLUSION_POLICY      = 'exclusion_policy';
    const EXCLUSION_POLICY_ALL  = 'all';
    const EXCLUSION_POLICY_NONE = 'none';

    const FIELDS = 'fields';

    const DESCRIPTION   = 'description';
    const DATA_TYPE     = 'data_type';
    const DEFAULT_VALUE = 'default_value';
    const ALLOW_ARRAY   = 'allow_array';
    const EXCLUDE       = 'exclude';
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
     * @return string
     */
    public static function getExclusionPolicy(array $config)
    {
        return isset($config[self::EXCLUSION_POLICY])
            ? $config[self::EXCLUSION_POLICY]
            : self::EXCLUSION_POLICY_NONE;
    }

    /**
     * @param array $config
     *
     * @return boolean
     */
    public static function isExcludeAll(array $config)
    {
        return
            isset($config[self::EXCLUSION_POLICY])
            && $config[self::EXCLUSION_POLICY] === self::EXCLUSION_POLICY_ALL;
    }

    /**
     * @param array $config
     *
     * @return boolean
     */
    public static function isExclude(array $config)
    {
        return
            isset($config[self::EXCLUDE])
            && $config[self::EXCLUDE];
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public static function getFields(array $config)
    {
        return !empty($config[self::FIELDS])
            ? $config[self::FIELDS]
            : [];
    }

    /**
     * @param array $config
     *
     * @return bool
     */
    public static function isRelationInherit(array $config)
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
            || self::isExclude($config)
            || (
                isset($config[self::EXCLUSION_POLICY])
                && $config[self::EXCLUSION_POLICY] !== self::EXCLUSION_POLICY_ALL
            );
    }
}
