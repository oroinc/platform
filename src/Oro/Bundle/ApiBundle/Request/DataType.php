<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * All the supported data-types of an incoming values which are implemented "out of the box".
 * New data-types can be added by implementing a value normalization processors.
 * @see Oro\Bundle\ApiBundle\Request\ValueNormalizer
 */
final class DataType
{
    const INTEGER          = 'integer';
    const BIGINT           = 'bigint';
    const UNSIGNED_INTEGER = 'unsignedInteger';
    const STRING           = 'string';
    const BOOLEAN          = 'boolean';
    const DECIMAL          = 'decimal';
    const FLOAT            = 'float';
    const DATETIME         = 'datetime';
    const ENTITY_TYPE      = 'entityType';
    const ENTITY_CLASS     = 'entityClass';
    const ORDER_BY         = 'orderBy';

    /**
     * Checks whether the field represents a nested object.
     *
     * @param string $dataType
     *
     * @return bool
     */
    public static function isNestedObject($dataType)
    {
        return 'nestedObject' === $dataType;
    }

    /**
     * Checks whether an association should be represented as a field.
     * For JSON.API it means that it should be in "attributes" section instead of "relationships" section.
     * Usually, to increase readability, "array" data-type is used for "to-many" associations
     * and "object" or "scalar" data-type is used for "to-one" associations.
     * The "object" is usually used if a value of such field contains several properties.
     * The "scalar" is usually used if a value of such field contains a scalar value.
     * Also "nested" data-type, that is used to group several fields in one object, is classified
     * as an association that should be represented as a field because the behaviour
     * of the nested object is the same.
     *
     * @param string $dataType
     *
     * @return bool
     */
    public static function isAssociationAsField($dataType)
    {
        return in_array(
            $dataType,
            ['array', 'object', 'scalar', 'nestedObject'],
            true
        );
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
        return 0 === strpos($dataType, 'association:');
    }

    /**
     * Checks whether the given data-type represents an inverse side of extended association.
     * See EntityExtendBundle/Resources/doc/associations.md for details about extended associations.
     *
     * @param string $dataType
     *
     * @return bool
     */
    public static function isExtendedInverseAssociation($dataType)
    {
        return 0 === strpos($dataType, 'inverseAssociation:');
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
        list($prefix, $type, $kind) = array_pad(explode(':', $dataType, 3), 3, null);
        if ('association' !== $prefix || empty($type) || '' === $kind) {
            throw new \InvalidArgumentException(
                sprintf('Expected a string like "association:type[:kind]", "%s" given.', $dataType)
            );
        }

        return [$type, $kind];
    }

    /**
     * Extracts the source class, type and the kind of an inverse extended association.
     * See EntityExtendBundle/Resources/doc/associations.md for details about extended associations.
     *
     * @param string $dataType
     *
     * @return string[] [source class name, association type, association kind]
     *
     * @throws \InvalidArgumentException if the given data-type does not represent an inverse part of
     *  extended association
     */
    public static function parseExtendedInverseAssociation($dataType)
    {
        list($prefix, $source, $type, $kind) = array_pad(explode(':', $dataType, 4), 4, null);
        if ('inverseAssociation' !== $prefix || empty($source) ||  empty($type) || '' === $kind) {
            throw new \InvalidArgumentException(
                sprintf('Expected a string like "inverseAssociation:/Source/Class:type[:kind]", "%s" given.', $dataType)
            );
        }

        return [$source, $type, $kind];
    }

    /**
     * Build the string that represent the inverse part of the extended association.
     * See EntityExtendBundle/Resources/doc/associations.md for details about extended associations.
     *
     * @param string $source
     * @param string $type
     * @param string $kind
     *
     * @return string
     */
    public static function buildExtendedInverseAssociation($source, $type, $kind = null)
    {
        $inverseAssociationDataTypeString = sprintf(
            'inverseAssociation:%s:%s',
            $source,
            $type
        );
        if ($kind) {
            $inverseAssociationDataTypeString = sprintf(
                '%s:%s',
                $inverseAssociationDataTypeString,
                $kind
            );
        }

        return $inverseAssociationDataTypeString;
    }
}
