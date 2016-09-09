<?php

namespace Oro\Component\EntitySerializer;

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
    const DISCRIMINATOR = '__discriminator__';

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
    const CLASS_NAME = '__class__';

    const FIELDS = EntityConfig::FIELDS;

    const EXCLUSION_POLICY      = EntityConfig::EXCLUSION_POLICY;
    const EXCLUSION_POLICY_ALL  = EntityConfig::EXCLUSION_POLICY_ALL;
    const EXCLUSION_POLICY_NONE = EntityConfig::EXCLUSION_POLICY_NONE;
    const DISABLE_PARTIAL_LOAD  = EntityConfig::DISABLE_PARTIAL_LOAD;
    const HINTS                 = EntityConfig::HINTS;
    const ORDER_BY              = EntityConfig::ORDER_BY;
    const MAX_RESULTS           = EntityConfig::MAX_RESULTS;
    const POST_SERIALIZE        = EntityConfig::POST_SERIALIZE;

    const PATH_DELIMITER = '.';

    const PROPERTY_PATH    = FieldConfig::PROPERTY_PATH;
    const EXCLUDE          = FieldConfig::EXCLUDE;
    const COLLAPSE         = FieldConfig::COLLAPSE;
    const DATA_TRANSFORMER = FieldConfig::DATA_TRANSFORMER;

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
        if (is_string($value)) {
            return [$value => null];
        }
        if (is_array($value)) {
            return $value;
        }

        throw new \UnexpectedValueException(
            sprintf(
                'Expected value of type "array, string or nothing", "%s" given.',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    /**
     * @param array $config The config of an entity
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
     * @return array
     */
    public static function hasFieldConfig($config, $field)
    {
        return
            !empty($config[self::FIELDS])
            && array_key_exists($field, $config[self::FIELDS]);
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
        return isset($config[self::FIELDS][$field])
            ? $config[self::FIELDS][$field]
            : [];
    }

    /**
     * Checks whether a property path represents some metadata property like '__class__' or '__discriminator__'
     *
     * @param string $propertyPath
     *
     * @return bool
     */
    public static function isMetadataProperty($propertyPath)
    {
        return strpos($propertyPath, '__') === 0;
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
        return explode(self::PATH_DELIMITER, $propertyPath);
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
            $result[$key] = is_object($val) ? clone $val : $val;
        }

        return $result;
    }
}
