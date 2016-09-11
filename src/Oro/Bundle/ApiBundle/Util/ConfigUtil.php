<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\EntitySerializer\ConfigUtil as BaseConfigUtil;

class ConfigUtil extends BaseConfigUtil
{
    /** the name of an entity configuration section */
    const DEFINITION = 'definition';

    /** the name of filters configuration section */
    const FILTERS = 'filters';

    /** the name of sorters configuration section */
    const SORTERS = 'sorters';

    /** the name of actions configuration section */
    const ACTIONS = 'actions';

    /** the name of response status codes configuration section */
    const STATUS_CODES = 'status_codes';

    /** the name of subresources configuration section */
    const SUBRESOURCES = 'subresources';

    /** a flag indicates whether an entity configuration should be merged with a configuration of a parent entity */
    const INHERIT = 'inherit';

    /**
     * @todo mark it deprecated in BAP-10066
     *
     * This is a temporary solution till ORM is used in EntitySerializer component.
     * You can use this constant as a property path for computed field
     * to avoid collisions with existing getters.
     * Example of usage:
     *  'fields' => [
     *      'primaryPhone' => ['property_path' => '_']
     *  ]
     * In this example a value of primaryPhone will not be loaded
     * even if an entity has getPrimaryPhone method.
     */
    const IGNORE_PROPERTY_PATH = '_';

    /**
     * @todo mark it deprecated in BAP-10066
     *
     * @param array $config The config of a field
     *
     * @return bool
     */
    public static function isIgnoredField(array $config)
    {
        return
            !empty($config[self::PROPERTY_PATH])
            && self::IGNORE_PROPERTY_PATH === $config[self::PROPERTY_PATH];
    }

    /**
     * Gets a native PHP array representation of each object in a given array.
     *
     * @param object[] $objects
     * @param bool     $treatEmptyAsNull
     *
     * @return array
     */
    public static function convertObjectsToArray(array $objects, $treatEmptyAsNull = false)
    {
        $result = [];
        foreach ($objects as $key => $value) {
            $arrayValue = $value->toArray();
            if (!empty($arrayValue)) {
                $result[$key] = $arrayValue;
            } elseif ($treatEmptyAsNull) {
                $result[$key] = null;
            }
        }

        return $result;
    }
}
