<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class ValueNormalizerUtil
{
    /**
     * Converts the entity class name to the entity type corresponding to a given request type.
     *
     * @param ValueNormalizer $valueNormalizer
     * @param string          $entityClass
     * @param RequestType     $requestType
     * @param bool            $throwException
     *
     * @return string|null
     *
     * @throws \Exception if the the entity type was not found and $throwException is TRUE
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
     * Converts the entity type to the class name corresponding to a given request type.
     *
     * @param ValueNormalizer $valueNormalizer
     * @param string          $entityType
     * @param RequestType     $requestType
     * @param bool            $throwException
     *
     * @return string|null
     *
     * @throws \Exception if the the entity class was not found and $throwException is TRUE
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
