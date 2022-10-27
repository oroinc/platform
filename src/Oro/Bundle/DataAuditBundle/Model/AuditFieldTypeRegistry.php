<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use Oro\Bundle\DataAuditBundle\Exception\UnsupportedDataTypeException;

/**
 * Class contains all auditable types and supported audit types together with mapping
 */
class AuditFieldTypeRegistry
{
    const COLLECTION_TYPE = 'collection';
    const TYPE_TEXT = 'text';
    const TYPE_STRING = 'string';

    /** @var string[] */
    protected static $typeMap = [
        'array' => 'array',
        'bigint' => 'integer',
        'boolean' => 'boolean',
        'currency' => 'text',
        'date' => 'date',
        'datetime' => 'datetime',
        'datetimetz' => 'datetimetz',
        'decimal' => 'float',
        'duration' => 'integer',
        'float' => 'float',
        'guid' => 'text',
        'integer' => 'integer',
        'json_array' => 'jsonarray',
        'money' => 'float',
        'money_value' => 'float',
        'object' => 'object',
        'percent' => 'float',
        'simple_array' => 'simplearray',
        'smallint' => 'integer',
        self::TYPE_STRING => 'text',
        self::TYPE_TEXT => 'text',
        'time' => 'time',

        'date_immutable' => false,
        'dateinterval' => false,
        'datetime_immutable' => false,
        'datetimeyz_immutable' => false,
        'json' => false,
        'time_immutable' => false,

        // collection types
        self::COLLECTION_TYPE => 'text',
        'manyToOne' => false,
        'oneToOne' => false,
        'manyToMany' => false,
        'oneToMany' => false,

        'ref-one' => false,
        'ref-many' => false,

        'enum' => false,
        'multiEnum' => false,

        // unsupported
        'config_object' => false,
        'file' => false,
        'image' => false,
        'binary' => false,
        'blob' => false,
        'crypted_string' => false,
        'wysiwyg' => 'text',
    ];

    /** @var string[] */
    protected static $auditTypes = [
        'array' => true,
        'simplearray' => true,
        'jsonarray' => true,
        'boolean' => true,
        'date' => true,
        'time' => true,
        'datetime' => true,
        'datetimetz' => true,
        'integer' => true,
        'float' => true,
        'object' => true,
        'text' => true,
    ];

    /**
     * @param string $doctrineType
     * @param string $auditType
     *
     * @throws \LogicException
     */
    public static function addType($doctrineType, $auditType)
    {
        static::validateType($doctrineType);

        static::$typeMap[$doctrineType] = $auditType;
    }

    /**
     * Removing type will cause application to crash if type is in use and the field is auditable
     *
     * @param string $doctrineType
     */
    public static function removeType($doctrineType)
    {
        unset(static::$typeMap[$doctrineType]);
    }

    /**
     * @param string $doctrineType
     *
     * @return bool
     */
    public static function hasType($doctrineType)
    {
        return !empty(static::$typeMap[$doctrineType]);
    }

    /**
     * @throws \LogicException
     */
    public static function addAuditType(string $auditType)
    {
        static::validateAuditType($auditType);

        static::$auditTypes[$auditType] = true;
    }

    /**
     * Removing type will cause application to crash if type is in use and the field is auditable
     */
    public static function removeAuditType(string $auditType)
    {
        static::validateType($auditType);

        unset(static::$auditTypes[$auditType]);
    }

    /**
     * @param string $auditType
     *
     * @return bool
     */
    public static function hasAuditType(string $auditType)
    {
        return !empty(static::$auditTypes[$auditType]);
    }

    /**
     * Replaces existing type. Make sure you move old data into new columns
     *
     * @param string $doctrineType
     * @param string $auditType
     */
    public static function overrideType($doctrineType, $auditType)
    {
        static::validateAuditType($auditType);

        static::$typeMap[$doctrineType] = $auditType;
    }

    /**
     * @param string $doctrineType
     * @return string
     *
     * @throws UnsupportedDataTypeException
     */
    public static function getAuditType($doctrineType)
    {
        if (!static::hasType($doctrineType)) {
            throw new UnsupportedDataTypeException($doctrineType);
        }

        return static::$typeMap[$doctrineType];
    }

    /**
     * @param string $doctrineType
     * @return string
     *
     * @throws UnsupportedDataTypeException
     */
    public static function isType($doctrineType)
    {
        return isset(static::$typeMap[$doctrineType]);
    }

    /**
     * @param string $auditType
     *
     * @return bool
     */
    public static function isAuditType(string $auditType)
    {
        return isset(static::$auditTypes[$auditType]);
    }

    protected static function validateType(string $doctrineType)
    {
        if (!empty(static::$typeMap[$doctrineType])) {
            throw new \LogicException(sprintf('Type %s already exists.', $doctrineType));
        }
    }

    protected static function validateAuditType(string $auditType)
    {
        if (!empty(static::$auditTypes[$auditType])) {
            throw new \LogicException(sprintf('Unknown audit type %s.', $auditType));
        }
    }
}
