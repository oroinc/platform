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

    /** a flag indicates whether using of Doctrine partial objects is disabled */
    public const DISABLE_PARTIAL_LOAD = 'disable_partial_load';

    /** a list Doctrine query hints */
    public const HINTS = 'hints';

    /** a list of associations for which INNER JOIN should be used instead of LEFT JOIN */
    public const INNER_JOIN_ASSOCIATIONS = 'inner_join_associations';

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

    /**
     * a query that should be used to load an association data
     * @see \Oro\Component\EntitySerializer\AssociationQuery
     */
    public const ASSOCIATION_QUERY = 'association_query';

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

    public static function getArrayValue(array $config, string $key): array
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

        throw new \UnexpectedValueException(sprintf(
            'Expected value of type "array, string or nothing", "%s" given.',
            get_debug_type($value)
        ));
    }

    public static function getExclusionPolicy(array $config): string
    {
        return $config[self::EXCLUSION_POLICY] ?? self::EXCLUSION_POLICY_NONE;
    }

    public static function isExcludeAll(array $config): bool
    {
        return
            isset($config[self::EXCLUSION_POLICY])
            && $config[self::EXCLUSION_POLICY] === self::EXCLUSION_POLICY_ALL;
    }

    public static function isExclude(array $config): bool
    {
        return
            isset($config[self::EXCLUDE])
            && $config[self::EXCLUDE];
    }

    public static function isCollapse(array $config): bool
    {
        return
            isset($config[self::COLLAPSE])
            && $config[self::COLLAPSE];
    }

    public static function isPartialAllowed(array $config): bool
    {
        return
            !isset($config[self::DISABLE_PARTIAL_LOAD])
            || !$config[self::DISABLE_PARTIAL_LOAD];
    }

    public static function hasFieldConfig(array $config, string $field): bool
    {
        return
            !empty($config[self::FIELDS])
            && \array_key_exists($field, $config[self::FIELDS]);
    }

    public static function getFieldConfig(array $config, string $field): array
    {
        return $config[self::FIELDS][$field] ?? [];
    }

    /**
     * Splits a property path to parts.
     *
     * @param string $propertyPath
     *
     * @return string[]
     */
    public static function explodePropertyPath(string $propertyPath): array
    {
        return explode(self::PATH_DELIMITER, $propertyPath);
    }

    /**
     * Makes a deep copy of an array of objects.
     *
     * @param object[] $objects
     *
     * @return object[]
     */
    public static function cloneObjects(array $objects): array
    {
        $result = [];
        foreach ($objects as $key => $val) {
            $result[$key] = clone $val;
        }

        return $result;
    }

    /**
     * Makes a deep copy of an array of configuration options.
     */
    public static function cloneItems(array $items): array
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
