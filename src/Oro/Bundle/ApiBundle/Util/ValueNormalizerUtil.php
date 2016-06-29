<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class ValueNormalizerUtil
{
    /**
     * Transforms a given class name to a human-readable representation.
     * e.g. "Symfony\Component\Form\Exception\InvalidArgumentException" will be transformed
     * to "invalid argument exception";
     * "\InvalidArgumentException" will be transformed to "invalid argument exception" as well.
     *
     * @param string      $className
     * @param string|null $classSuffix
     *
     * @return string
     */
    public static function humanizeClassName($className, $classSuffix = null)
    {
        $delimiter = strrpos($className, '\\');
        if (false !== $delimiter) {
            $className = substr($className, $delimiter + 1);
        }
        // remove $classSuffix if $className already has it
        if ($classSuffix && substr($className, -strlen($classSuffix)) === $classSuffix) {
            $classSuffix = null;
        }

        $result = strtolower(preg_replace('/(?<=\\w)([A-Z\\\\])/', ' $1', $className));
        if ($classSuffix) {
            $result .= ' ' . strtolower($classSuffix);
        }

        return $result;
    }

    /**
     * Converts the entity class name to the entity type corresponding to the given request type.
     *
     * @param ValueNormalizer $valueNormalizer
     * @param string          $entityClass
     * @param RequestType     $requestType
     * @param bool            $throwException
     *
     * @return string|null
     *
     * @throws \Exception if the entity type was not found and $throwException is TRUE
     */
    public static function convertToEntityType(
        ValueNormalizer $valueNormalizer,
        $entityClass,
        RequestType $requestType,
        $throwException = true
    ) {
        try {
            return $valueNormalizer->normalizeValue(
                $entityClass,
                DataType::ENTITY_TYPE,
                $requestType
            );
        } catch (\Exception $e) {
            if ($throwException) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Converts the entity type corresponding to the given request type to the class name.
     *
     * @param ValueNormalizer $valueNormalizer
     * @param string          $entityType
     * @param RequestType     $requestType
     * @param bool            $throwException
     *
     * @return string|null
     *
     * @throws \Exception if the entity type is not associated with any class and $throwException is TRUE
     */
    public static function convertToEntityClass(
        ValueNormalizer $valueNormalizer,
        $entityType,
        RequestType $requestType,
        $throwException = true
    ) {
        try {
            return $valueNormalizer->normalizeValue(
                $entityType,
                DataType::ENTITY_CLASS,
                $requestType
            );
        } catch (\Exception $e) {
            if ($throwException) {
                throw $e;
            }
        }

        return null;
    }
}
