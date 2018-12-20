<?php

namespace Oro\Component\EntitySerializer;

/**
 * Provides a set of configuration related reusable constants and static methods.
 */
class ConfigUtil
{
    /**
     * The field name or property path of the field which can be used to get the discriminator value of the entity.
     * Example of usage:
     *  'fields' => [
     *      'type' => ['property_path' => '__discriminator__']
     *  ]
     * or
     *  'fields' => [
     *      '__discriminator__' => null
     *  ]
     */
    public const DISCRIMINATOR = '__discriminator__';

    /**
     * The field name or property path of the field which can be used to get FQCN of the entity.
     * Example of usage:
     *  'fields' => [
     *      'entity' => ['property_path' => '__class__']
     *  ]
     * or
     *  'fields' => [
     *      '__class__' => null
     *  ]
     */
    public const CLASS_NAME = '__class__';

    /**
     * a key of a record contains an additional information about a collection,
     * e.g. "has_more" flag indicates whether a collection has more records than it was requested.
     */
    public const INFO_RECORD_KEY = '_';

    /** a list of fields */
    public const FIELDS = 'fields';

    /** a type of the exclusion strategy that should be used for the entity */
    public const EXCLUSION_POLICY = 'exclusion_policy';

    /** exclude all fields are not configured explicitly */
    public const EXCLUSION_POLICY_ALL = 'all';

    /** exclude only fields are marked as excluded */
    public const EXCLUSION_POLICY_NONE = 'none';

    /** a flag indicates whether using of Doctrine partial object is disabled */
    public const DISABLE_PARTIAL_LOAD = 'disable_partial_load';

    /** a list Doctrine query hints */
    public const HINTS = 'hints';

    /** the ordering of the result */
    public const ORDER_BY = 'order_by';

    /** the maximum number of items in the result */
    public const MAX_RESULTS = 'max_results';

    /**
     * a flag indicates whether an additional element with
     * key "_" {@see ConfigUtil::INFO_RECORD_KEY} and value ['has_more' => true]
     * should be added to a collection if it has more records than it was requested.
     */
    public const HAS_MORE = 'has_more';

    /** a handler that can be used to modify serialized data for a single item */
    public const POST_SERIALIZE = 'post_serialize';

    /** a handler that can be used to modify serialized data for a list of items */
    public const POST_SERIALIZE_COLLECTION = 'post_serialize_collection';

    /** a flag indicates whether the field should be excluded */
    public const EXCLUDE = 'exclude';

    /** the path of the field value */
    public const PROPERTY_PATH = 'property_path';

    /**
     * a flag indicates whether the target entity should be collapsed;
     * it means that target entity should be returned as a value, instead of an array with values of entity fields;
     * usually it is used to get identifier of the related entity
     */
    public const COLLAPSE = 'collapse';

    /** the data transformer to be applies to the field value */
    public const DATA_TRANSFORMER = 'data_transformer';

    /** a symbol that is used to delimit element in a path */
    public const PATH_DELIMITER = '.';

    /** @internal filled automatically by ConfigConverter and used only during serialization */
    public const COLLAPSE_FIELD = '_collapse_field';

    /** @internal filled automatically by ConfigConverter and used only during serialization */
    public const EXCLUDED_FIELDS = '_excluded_fields';

    /** @internal filled automatically by ConfigConverter and used only during serialization */
    public const RENAMED_FIELDS = '_renamed_fields';

    /**
     * @param array  $config A config
     * @param string $key    A config key
     *
     * @return array
     */
    public static function getArrayValue(array $config, $key)
    {
        if (!isset($config[$key])) {
            return [];
        }

        $value = $config[$key];
        if (\is_string($value)) {
            return [$value => null];
        }
        if (\is_array($value)) {
            return $value;
        }

        throw new \UnexpectedValueException(\sprintf(
            'Expected value of type "array, string or nothing", "%s" given.',
            \is_object($value) ? \get_class($value) : \gettype($value)
        ));
    }

    /**
     * @param array $config The config of an entity
     *
     * @return string
     */
    public static function getExclusionPolicy(array $config)
    {
        return $config[self::EXCLUSION_POLICY] ?? self::EXCLUSION_POLICY_NONE;
    }

    /**
     * @param array $config The config of an entity
     *
     * @return bool
     */
    public static function isExcludeAll(array $config)
    {
        return
            isset($config[self::EXCLUSION_POLICY])
            && $config[self::EXCLUSION_POLICY] === self::EXCLUSION_POLICY_ALL;
    }

    /**
     * @param array $config The config of a field
     *
     * @return bool
     */
    public static function isExclude(array $config)
    {
        return
            isset($config[self::EXCLUDE])
            && $config[self::EXCLUDE];
    }

    /**
     * @param array $config The config of a field
     *
     * @return bool
     */
    public static function isCollapse(array $config)
    {
        return
            isset($config[self::COLLAPSE])
            && $config[self::COLLAPSE];
    }

    /**
     * @param array $config The config of an entity
     *
     * @return bool
     */
    public static function isPartialAllowed($config)
    {
        return
            !isset($config[self::DISABLE_PARTIAL_LOAD])
            || !$config[self::DISABLE_PARTIAL_LOAD];
    }

    /**
     * Checks if the specified field has some special configuration
     *
     * @param array  $config The config of an entity the specified field belongs
     * @param string $field  The name of the field
     *
     * @return bool
     */
    public static function hasFieldConfig($config, $field)
    {
        return
            !empty($config[self::FIELDS])
            && \array_key_exists($field, $config[self::FIELDS]);
    }

    /**
     * Returns a configuration of the specified field
     *
     * @param array  $config The config of an entity the specified field belongs
     * @param string $field  The name of the field
     *
     * @return array
     */
    public static function getFieldConfig($config, $field)
    {
        return $config[self::FIELDS][$field] ?? [];
    }

    /**
     * Splits a property path to parts
     *
     * @param string $propertyPath
     *
     * @return string[]
     */
    public static function explodePropertyPath($propertyPath)
    {
        return \explode(self::PATH_DELIMITER, $propertyPath);
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
        $result = [];
        foreach ($objects as $key => $val) {
            $result[$key] = clone $val;
        }

        return $result;
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
        $result = [];
        foreach ($items as $key => $val) {
            if (\is_object($val)) {
                $val = clone $val;
            }
            $result[$key] = $val;
        }

        return $result;
    }
}
