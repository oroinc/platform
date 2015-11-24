<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\EntitySerializer\ConfigUtil as BaseConfigUtil;

class ConfigUtil extends BaseConfigUtil
{
    const DEFINITION = 'definition';
    const FILTERS    = 'filters';
    const SORTERS    = 'sorters';

    const LABEL         = 'label';
    const DESCRIPTION   = 'description';
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
