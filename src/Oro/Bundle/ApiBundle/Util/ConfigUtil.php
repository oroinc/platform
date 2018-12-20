<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Component\ChainProcessor\ToArrayInterface;
use Oro\Component\EntitySerializer\ConfigUtil as BaseConfigUtil;

/**
 * Provides a set of Data API configuration related reusable constants and static methods.
 */
class ConfigUtil extends BaseConfigUtil
{
    /** the name of an entity configuration section */
    public const DEFINITION = 'definition';

    /** the name of filters configuration section */
    public const FILTERS = 'filters';

    /** the name of sorters configuration section */
    public const SORTERS = 'sorters';

    /** the name of actions configuration section */
    public const ACTIONS = 'actions';

    /** the name of response status codes configuration section */
    public const STATUS_CODES = 'status_codes';

    /** the name of subresources configuration section */
    public const SUBRESOURCES = 'subresources';

    /** exclude all custom fields that are not configured explicitly */
    public const EXCLUSION_POLICY_CUSTOM_FIELDS = 'custom_fields';

    /** a flag indicates whether an entity configuration should be merged with a configuration of a parent entity */
    public const INHERIT = 'inherit';

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
    public const IGNORE_PROPERTY_PATH = '_';

    /** a short, human-readable description of API resource, sub-resource, field, etc. */
    public const DESCRIPTION = 'description';

    /** a detailed documentation of API resource, sub-resource, etc. */
    public const DOCUMENTATION = 'documentation';

    /** resource link to a .md file that will be used to retrieve a documentation */
    public const DOCUMENTATION_RESOURCE = 'documentation_resource';

    /** a human-readable description of the API resource identifier */
    public const IDENTIFIER_DESCRIPTION = 'identifier_description';

    /** the names of identifier fields of the entity */
    public const IDENTIFIER_FIELD_NAMES = 'identifier_field_names';

    /** the class name of a parent API resource */
    public const PARENT_RESOURCE_CLASS = 'parent_resource_class';

    /** the name of ACL resource that should be used to protect the entity */
    public const ACL_RESOURCE = 'acl_resource';

    /** the default page size */
    public const PAGE_SIZE = 'page_size';

    /** a flag indicates whether a sorting is disabled */
    public const DISABLE_SORTING = 'disable_sorting';

    /** a flag indicates whether an inclusion of related entities is disabled */
    public const DISABLE_INCLUSION = 'disable_inclusion';

    /** a flag indicates whether a requesting of a restricted set of fields is disabled */
    public const DISABLE_FIELDSET = 'disable_fieldset';

    /** a flag indicates whether a requesting of additional meta properties is disabled */
    public const DISABLE_META_PROPERTIES = 'disable_meta_properties';

    /** a handler that should be used to delete the entity */
    public const DELETE_HANDLER = 'delete_handler';

    /** the form type that should be used for the entity */
    public const FORM_TYPE = 'form_type';

    /** the form options that should be used for the entity */
    public const FORM_OPTIONS = 'form_options';

    /** event subscriber that should be used for the entity form */
    public const FORM_EVENT_SUBSCRIBER = 'form_event_subscriber';

    /** the data type of the field value */
    public const DATA_TYPE = 'data_type';

    /** a value that indicates whether the field is input-only, output-only or bidirectional */
    public const DIRECTION = 'direction';

    /** a value for the direction option that indicates whether the field is input-only */
    public const DIRECTION_INPUT_ONLY = 'input-only';

    /** a value for the direction option that indicates whether the field is output-only */
    public const DIRECTION_OUTPUT_ONLY = 'output-only';

    /** a value for the direction option that indicates whether the field is bidirectional */
    public const DIRECTION_BIDIRECTIONAL = 'bidirectional';

    /** a flag indicates whether the field represents a meta information */
    public const META_PROPERTY = 'meta_property';

    /** the name by which the meta property should be returned in the response */
    public const META_PROPERTY_RESULT_NAME = 'meta_property_result_name';

    /** the class name of a target entity */
    public const TARGET_CLASS = 'target_class';

    /**
     * the type of a target association, can be "to-one" or "to-many",
     * also "collection" can be used in Resources/config/oro/api.yml file as an alias for "to-many"
     */
    public const TARGET_TYPE = 'target_type';

    /** a list of fields on which this field depends on */
    public const DEPENDS_ON = 'depends_on';

    /** the type of the filter */
    public const FILTER_TYPE = 'type';

    /** the filter options  */
    public const FILTER_OPTIONS = 'options';

    /** a list of operators supported by the filter */
    public const FILTER_OPERATORS = 'operators';

    /** a flag indicates whether the filter value can be an array */
    public const ALLOW_ARRAY = 'allow_array';

    /** a flag indicates whether the filter value can be a pair of "from" and "to" values */
    public const ALLOW_RANGE = 'allow_range';

    /** a flag indicates whether the filter represents a collection valued association */
    public const COLLECTION = 'collection';

    /**
     * a key inside a record contains an additional information about a collection of primary objects
     * that is used to specify the current page number
     * @see \Oro\Component\EntitySerializer\ConfigUtil::INFO_RECORD_KEY
     */
    public const PAGE_NUMBER = 'page_number';

    /**
     * Gets a native PHP array representation of each object in a given array.
     *
     * @param ToArrayInterface[] $objects
     * @param bool               $treatEmptyAsNull
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
     * Gets a native PHP array representation of each property object in a given array.
     *
     * @param ToArrayInterface[] $properties
     *
     * @return array
     */
    public static function convertPropertiesToArray(array $properties)
    {
        $result = [];
        foreach ($properties as $name => $property) {
            $data = $property->toArray();
            unset($data['name']);
            $result[$name] = $data;
        }

        return $result;
    }

    /**
     * Gets a native PHP array representation of the given configuration options.
     *
     * @param array $items
     *
     * @return array
     */
    public static function convertItemsToArray(array $items)
    {
        $result = $items;
        foreach ($items as $key => $value) {
            if (\is_object($value) && \method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
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
        return \sprintf('__%s__', $resultName);
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
            self::buildMetaPropertyName($resultName)
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
