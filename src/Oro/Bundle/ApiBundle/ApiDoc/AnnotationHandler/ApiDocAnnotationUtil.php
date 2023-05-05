<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * A set of utility methods for performing reflective operations on ApiDoc object.
 * Unfortunately ApiDoc class does not have enough getters and setters
 * and there is no other way to get or to set a value of some properties, except to use the reflection.
 */
final class ApiDocAnnotationUtil
{
    private static ?\ReflectionClass $reflClass = null;
    private static ?\ReflectionProperty $statusCodesReflProperty = null;
    private static ?\ReflectionProperty $filtersReflProperty = null;
    private static ?\ReflectionProperty $inputReflProperty = null;
    private static ?\ReflectionProperty $outputReflProperty = null;

    /**
     * @param ApiDoc $annotation
     *
     * @return array [status code => description, ...]
     */
    public static function getStatusCodes(ApiDoc $annotation): array
    {
        if (null === self::$statusCodesReflProperty) {
            self::$statusCodesReflProperty = ReflectionUtil::getProperty(self::getReflectionClass(), 'statusCodes');
            self::$statusCodesReflProperty->setAccessible(true);
        }

        return self::$statusCodesReflProperty->getValue($annotation);
    }

    public static function setFilters(ApiDoc $annotation, array $filters): void
    {
        if (null === self::$filtersReflProperty) {
            self::$filtersReflProperty = ReflectionUtil::getProperty(self::getReflectionClass(), 'filters');
            self::$filtersReflProperty->setAccessible(true);
        }

        self::$filtersReflProperty->setValue($annotation, $filters);
    }

    public static function setInput(ApiDoc $annotation, array $input): void
    {
        if (null === self::$inputReflProperty) {
            self::$inputReflProperty = ReflectionUtil::getProperty(self::getReflectionClass(), 'input');
            self::$inputReflProperty->setAccessible(true);
        }

        self::$inputReflProperty->setValue($annotation, $input);
    }

    public static function setOutput(ApiDoc $annotation, array $input): void
    {
        if (null === self::$outputReflProperty) {
            self::$outputReflProperty = ReflectionUtil::getProperty(self::getReflectionClass(), 'output');
            self::$outputReflProperty->setAccessible(true);
        }

        self::$outputReflProperty->setValue($annotation, $input);
    }

    private static function getReflectionClass(): \ReflectionClass
    {
        if (null === self::$reflClass) {
            self::$reflClass = new \ReflectionClass(ApiDoc::class);
        }

        return self::$reflClass;
    }
}
