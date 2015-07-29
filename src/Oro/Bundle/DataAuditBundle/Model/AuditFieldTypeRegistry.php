<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use LogicException;

use Oro\Bundle\DataAuditBundle\Exception\UnsupportedDataTypeException;

class AuditFieldTypeRegistry
{
    /** @var string[] */
    protected static $typeMap = [
        'boolean'    => 'boolean',
        'text'       => 'text',
        'string'     => 'text',
        'guid'       => 'text',
        'manyToOne'  => 'text',
        'enum'       => 'text',
        'multiEnum'  => 'text',
        'ref-many'   => 'text',
        'ref-one'    => 'text',
        'smallint'   => 'integer',
        'integer'    => 'integer',
        'bigint'     => 'integer',
        'decimal'    => 'float',
        'float'      => 'float',
        'money'      => 'float',
        'percent'    => 'float',
        'date'       => 'date',
        'time'       => 'time',
        'datetime'   => 'datetime',
        'datetimetz' => 'datetimetz',
        'object'     => 'object',
        'array'      => 'array',
        'simple_array' => 'simplearray',
        'json_array'   => 'jsonarray',
    ];

    /**
     * @param type $doctrineType
     * @param type $auditType
     *
     * @throws LogicException
     */
    public static function addType($doctrineType, $auditType)
    {
        if (isset(static::$typeMap[$doctrineType])) {
            throw new LogicException(sprintf('Type %s already exists.', $doctrineType));
        }

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
     * Replaces existing type. Make sure you move old data into new columns
     *
     * @param string $doctrineType
     * @param string $auditType
     */
    public static function overrideType($doctrineType, $auditType)
    {
        static::$typeMap[$doctrineType] = $auditType;
    }

    /**
     * @param string $doctrineType
     *
     * @return bool
     */
    public static function hasType($doctrineType)
    {
        return isset(static::$typeMap[$doctrineType]);
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
}
