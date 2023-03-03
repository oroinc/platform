<?php

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Doctrine\Inflector\Inflector;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;
use Symfony\Component\String\Inflector\EnglishInflector;

/**
 * Generates names for entity fields accessors (getter, setter, adder, remover)
 */
class EntityFieldAccessorsHelper
{
    private static ?EnglishInflector $symfonyInflector = null;
    private static ?Inflector $inflector = null;

    private static function getSingular(string $fieldName): string
    {
        if (!self::$symfonyInflector) {
            self::$symfonyInflector = new EnglishInflector();
        }

        $singular = self::$symfonyInflector->singularize(self::getInflector()->classify($fieldName));

        return \reset($singular);
    }

    public static function getterName(string $fieldName): string
    {
        return 'get'.\ucfirst(self::getInflector()->camelize($fieldName));
    }

    public static function setterName(string $fieldName): string
    {
        return 'set'.\ucfirst(self::getInflector()->camelize($fieldName));
    }

    public static function adderName(string $fieldName): string
    {
        return 'add'.\ucfirst(self::getSingular($fieldName));
    }

    public static function removerName(string $fieldName): string
    {
        return 'remove'.\ucfirst(self::getSingular($fieldName));
    }

    /**
     * @return Inflector
     */
    public static function getInflector(): Inflector
    {
        if (!self::$inflector) {
            self::$inflector = InflectorFactory::create();
        }

        return self::$inflector;
    }
}
