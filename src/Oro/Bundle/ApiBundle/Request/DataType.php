<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Provides a list of all the supported data-types of an incoming values which are implemented "out of the box".
 * New data-types can be added by implementing a value normalization processors.
 * @see \Oro\Bundle\ApiBundle\Request\ValueNormalizer
 * Also provides a set of methods to simplify work with definition of complex data-types,
 * like nested and extended associations.
 */
final class DataType
{
    public const INTEGER          = 'integer';
    public const SMALLINT         = 'smallint';
    public const BIGINT           = 'bigint';
    public const UNSIGNED_INTEGER = 'unsignedInteger';
    public const STRING           = 'string';
    public const BOOLEAN          = 'boolean';
    public const DECIMAL          = 'decimal';
    public const FLOAT            = 'float';
    public const DATETIME         = 'datetime';
    public const DATE             = 'date';
    public const TIME             = 'time';
    public const PERCENT          = 'percent'; // a percentage value, 100% equals to 1
    public const PERCENT_100      = 'percent_100'; // a percentage value multiplied by 100, 100% equals to 100
    public const MONEY            = 'money';
    public const DURATION         = 'duration';
    public const GUID             = 'guid';
    public const ARRAY            = 'array';
    public const OBJECT           = 'object';
    public const OBJECTS          = 'objects';
    public const SCALAR           = 'scalar';
    public const ENTITY_TYPE      = 'entityType';
    public const ENTITY_CLASS     = 'entityClass';
    public const ORDER_BY         = 'orderBy';

    private const NESTED_OBJECT                   = 'nestedObject';
    private const NESTED_ASSOCIATION              = 'nestedAssociation';
    private const EXTENDED_ASSOCIATION_PREFIX     = 'association';
    private const EXTENDED_ASSOCIATION_MARKER     = 'association:';
    private const ASSOCIATION_AS_FIELD_DATA_TYPES = ['array', 'object', 'nestedObject', 'objects', 'scalar'];

    /**
     * Checks whether the field represents a nested object.
     *
     * @param string $dataType
     *
     * @return bool
     */
    public static function isNestedObject($dataType)
    {
        return self::NESTED_OBJECT === $dataType;
    }

    /**
     * Checks whether the field represents a nested association.
     *
     * @param string $dataType
     *
     * @return bool
     */
    public static function isNestedAssociation($dataType)
    {
        return self::NESTED_ASSOCIATION === $dataType;
    }

    /**
     * Checks whether an association should be represented as a field.
     * For JSON:API it means that it should be in "attributes" section instead of "relationships" section.
     * Usually, to increase readability, "scalar" and "object" data-types are used for "to-one" associations
     * and "array" and "objects" data-types are used for "to-many" associations.
     * The "scalar" is usually used if a value of the field contains a scalar value.
     * The "array" is usually used if a value of the field contains a list of scalar values.
     * The "object" is usually used if a value of the field contains several properties.
     * The "objects" is usually used if a value of the field contains a list of items that have several properties.
     * Also "nestedObject" data-type, that is used to group several fields in one object,
     * is classified as an association that should be represented as a field because the behaviour
     * of it is the same.
     *
     * @param string $dataType
     *
     * @return bool
     */
    public static function isAssociationAsField($dataType)
    {
        return \in_array($dataType, self::ASSOCIATION_AS_FIELD_DATA_TYPES, true);
    }

    /**
     * Checks whether the given data-type represents an extended association.
     * See EntityExtendBundle/Resources/doc/associations.md for details about extended associations.
     *
     * @param string $dataType
     *
     * @return bool
     */
    public static function isExtendedAssociation($dataType)
    {
        return 0 === \strpos($dataType, self::EXTENDED_ASSOCIATION_MARKER);
    }

    /**
     * Extracts the type and the kind of an extended association.
     * See EntityExtendBundle/Resources/doc/associations.md for details about extended associations.
     *
     * @param string $dataType
     *
     * @return string[] [association type, association kind]
     *
     * @throws \InvalidArgumentException if the given data-type does not represent an extended association
     */
    public static function parseExtendedAssociation($dataType)
    {
        list($prefix, $type, $kind) = \array_pad(\explode(':', $dataType, 3), 3, null);
        if (self::EXTENDED_ASSOCIATION_PREFIX !== $prefix || empty($type) || '' === $kind) {
            throw new \InvalidArgumentException(
                \sprintf('Expected a string like "association:type[:kind]", "%s" given.', $dataType)
            );
        }

        return [$type, $kind];
    }
}
