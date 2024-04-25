<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;

/**
 * Provides methods to generate method names for extended relations.
 */
class AssociationNameGenerator
{
    protected const NAME_PREFIXES = ['get', 'set', 'has', 'add', 'remove', 'reset', 'support'];
    protected const NAME_POSTFIXES = ['Targets', 'Target'];

    /**
     * Converts a string into a "class-name-like" name, e.g. 'first_name' to 'FirstName'.
     *
     * @param string $string
     *
     * @return string
     */
    public static function classify($string)
    {
        return InflectorFactory::create()->classify(null === $string ? '' : $string);
    }

    /**
     * Generates method name to checks if an entity can be associated with another entity
     *
     * @param string|null $associationKind The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function generateSupportTargetMethodName($associationKind)
    {
        return sprintf('support%sTarget', self::classify($associationKind));
    }

    /**
     * Generates method name to get associated entity
     *
     * @param string|null $associationKind The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function generateGetTargetMethodName($associationKind)
    {
        return sprintf('get%sTarget', self::classify($associationKind));
    }

    /**
     * Generates method name to get associated entities
     *
     * @param string|null $associationKind The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function generateGetTargetsMethodName($associationKind)
    {
        return sprintf('get%sTargets', self::classify($associationKind));
    }

    /**
     * Generates method name to set association to another entity
     *
     * @param string|null $associationKind The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function generateSetTargetMethodName($associationKind)
    {
        return sprintf('set%sTarget', self::classify($associationKind));
    }

    /**
     * Generates method name to reset associations
     *
     * @param string|null $associationKind The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function generateResetTargetsMethodName($associationKind)
    {
        return sprintf('reset%sTargets', self::classify($associationKind));
    }

    /**
     * Generates method name to add association to another entity
     *
     * @param string|null $associationKind The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function generateAddTargetMethodName($associationKind)
    {
        return sprintf('add%sTarget', self::classify($associationKind));
    }

    /**
     * Generates method name to check if entity is associated with another entity
     *
     * @param string|null $associationKind The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function generateHasTargetMethodName($associationKind)
    {
        return sprintf('has%sTarget', self::classify($associationKind));
    }

    /**
     * Generates method name to remove association
     *
     * @param string|null $associationKind The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function generateRemoveTargetMethodName($associationKind)
    {
        return sprintf('remove%sTarget', self::classify($associationKind));
    }

    /**
     * Extract association kind from method name:
     * [
     *    'getSourceTarget' => 'source'
     *    'getSourceListTarget' => 'sourceList'
     *    'getTarget' => null
     * ]
     */
    public static function extractAssociationKind(string $methodName): ?string
    {
        $methodSplit = preg_split('/(?=[A-Z])/', $methodName);
        if (count($methodSplit) < 3) {
            return null;
        }
        if (in_array($methodSplit[0], self::NAME_PREFIXES)
            && in_array(end($methodSplit), self::NAME_POSTFIXES)) {
            return lcfirst(implode('', array_slice($methodSplit, 1, -1)));
        }

        return null;
    }
}
