<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

/**
 * Provides a set of static methods that can be used to simplify converting
 * an entity class name to API entity type and vise versa.
 * In additional this class can be used to transform any class, usually an exception
 * or a validation constraint, to a human-readable representation.
 */
class ValueNormalizerUtil
{
    /**
     * Transforms a given class name to a human-readable representation.
     * e.g. "Symfony\Component\Form\Exception\InvalidArgumentException" will be transformed
     * to "invalid argument exception";
     * "\InvalidArgumentException" will be transformed to "invalid argument exception" as well.
     */
    public static function humanizeClassName(string $className, ?string $classSuffix = null): string
    {
        // get short class name
        $delimiter = strrpos($className, '\\');
        if (false !== $delimiter) {
            $className = substr($className, $delimiter + 1);
        }
        // divide class name into words
        $result = strtolower(preg_replace('/(?<=\\w)([A-Z\\\\])/', ' $1', $className));
        // remove "_" characters, fix abbreviations and remove redundant whitespaces
        $result = preg_replace(
            '/(?<!\w{2}) (?!\w{2})/',
            '',
            preg_replace('/\W+/', ' ', str_replace('_', ' ', $result))
        );
        // add suffix
        if ($classSuffix) {
            $suffix = strtolower($classSuffix);
            if ($result !== $suffix) {
                $suffix = ' ' . $suffix;
                if (!str_ends_with($result, $suffix)) {
                    $result .= $suffix;
                }
            }
        }

        return $result;
    }

    /**
     * Converts the entity class name to the entity type corresponding to the given request type.
     *
     * @throws EntityAliasNotFoundException when the entity type was not found
     */
    public static function convertToEntityType(
        ValueNormalizer $valueNormalizer,
        string $entityClass,
        RequestType $requestType
    ): string {
        return $valueNormalizer->normalizeValue($entityClass, DataType::ENTITY_TYPE, $requestType);
    }

    /**
     * Tries to convert the entity class name to the entity type corresponding to the given request type.
     * Returns NULL when the entity type was not found.
     */
    public static function tryConvertToEntityType(
        ValueNormalizer $valueNormalizer,
        string $entityClass,
        RequestType $requestType
    ): ?string {
        try {
            return $valueNormalizer->normalizeValue($entityClass, DataType::ENTITY_TYPE, $requestType);
        } catch (EntityAliasNotFoundException) {
        }

        return null;
    }

    /**
     * Converts the entity type corresponding to the given request type to the class name.
     *
     * @throws EntityAliasNotFoundException when the entity type is not associated with any class
     */
    public static function convertToEntityClass(
        ValueNormalizer $valueNormalizer,
        string $entityType,
        RequestType $requestType
    ): string {
        return $valueNormalizer->normalizeValue($entityType, DataType::ENTITY_CLASS, $requestType);
    }

    /**
     * Tries to convert the entity type corresponding to the given request type to the class name.
     * Returns NULL when the entity type is not associated with any class.
     */
    public static function tryConvertToEntityClass(
        ValueNormalizer $valueNormalizer,
        string $entityType,
        RequestType $requestType
    ): ?string {
        try {
            return $valueNormalizer->normalizeValue($entityType, DataType::ENTITY_CLASS, $requestType);
        } catch (EntityAliasNotFoundException) {
        }

        return null;
    }
}
