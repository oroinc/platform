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

    /**
     * Makes a deep copy of an array of objects.
     *
     * @param object[] $objects
     *
     * @return object[]
     */
    public static function cloneObjects(array $objects)
    {
        return array_map(
            function ($object) {
                return clone $object;
            },
            $objects
        );
    }

    /**
     * Makes a deep copy of an array of configuration options.
     *
     * @param array $items
     *
     * @return array
     */
    public static function cloneItems(array $items)
    {
        return array_map(
            function ($value) {
                return is_object($value) ? clone $value : $value;
            },
            $items
        );
    }
}
