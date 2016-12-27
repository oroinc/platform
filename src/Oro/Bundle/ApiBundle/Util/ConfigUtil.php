<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
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
     * You can use this constant as a property path for computed field
     * to avoid collisions with existing getters.
     * Example of usage:
     *  'fields' => [
     *      'primaryPhone' => ['property_path' => '_']
     *  ]
     * In this example a value of primaryPhone will not be loaded
     * even if an entity has getPrimaryPhone method.
     * Also such field will be marked as not mapped for Symfony forms.
     */
    const IGNORE_PROPERTY_PATH = '_';

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
     * Returns the name by which the meta property could be stored in a configuration of an entity.
     * This method can be used to avoid collisions between names of meta properties and entity fields.
     *
     * @param string $resultName The name by which the meta property should be returned in the response
     *
     * @return string
     */
    public static function buildMetaPropertyName($resultName)
    {
        return sprintf('__%s__', $resultName);
    }

    /**
     * Returns the property path of a meta property by its result name.
     *
     * @param string                 $resultName The name by which the meta property should be returned in the response
     * @param EntityDefinitionConfig $config     The entity configuration
     *
     * @return string|null The property path if the requested meta property exists in the entity configuration
     *                     and it is not excluded; otherwise, NULL.
     */
    public static function getPropertyPathOfMetaProperty($resultName, EntityDefinitionConfig $config)
    {
        $fieldName = $config->findFieldNameByPropertyPath(
            ConfigUtil::buildMetaPropertyName($resultName)
        );
        if (!$fieldName) {
            return null;
        }
        $field = $config->getField($fieldName);
        if ($field->isExcluded() || !$field->isMetaProperty()) {
            return null;
        }

        return $field->getPropertyPath($fieldName);
    }
}
