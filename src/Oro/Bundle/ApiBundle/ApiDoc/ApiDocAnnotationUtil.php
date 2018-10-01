<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * A set of utility methods for performing reflective operations on ApiDoc object.
 * Unfortunately ApiDoc class does not have enought getters and setters
 * and there is no other way to get or to set a value of some properties, except to use the reflection.
 */
final class ApiDocAnnotationUtil
{
    /**
     * @param ApiDoc $annotation
     *
     * @return array [status code => description, ...]
     */
    public static function getStatusCodes(ApiDoc $annotation): array
    {
        return self::getReflectionProperty($annotation, 'statusCodes')->getValue($annotation);
    }

    /**
     * @param ApiDoc $annotation
     * @param array  $filters
     */
    public static function setFilters(ApiDoc $annotation, array $filters): void
    {
        self::getReflectionProperty($annotation, 'filters')->setValue($annotation, $filters);
    }

    /**
     * @param ApiDoc $annotation
     * @param array  $input
     */
    public static function setInput(ApiDoc $annotation, array $input): void
    {
        self::getReflectionProperty($annotation, 'input')->setValue($annotation, $input);
    }

    /**
     * @param ApiDoc $annotation
     * @param array  $input
     */
    public static function setOutput(ApiDoc $annotation, array $input): void
    {
        self::getReflectionProperty($annotation, 'output')->setValue($annotation, $input);
    }

    /**
     * @param ApiDoc $annotation
     * @param string $propertyName
     *
     * @return \ReflectionProperty
     */
    private static function getReflectionProperty(ApiDoc $annotation, string $propertyName): \ReflectionProperty
    {
        $property = ReflectionUtil::getProperty(new \ReflectionClass($annotation), $propertyName);
        $property->setAccessible(true);

        return $property;
    }
}
