<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendHelper
{
    const ENTITY_NAMESPACE = 'Extend\\Entity\\';

    const MAX_ENUM_VALUE_ID_LENGTH = 32;
    const MAX_ENUM_SNAPSHOT_LENGTH = 500;
    const BASE_ENUM_VALUE_CLASS    = 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue';
    const ENUM_SNAPSHOT_SUFFIX     = 'Snapshot';

    /**
     * @param string $type
     *
     * @return string
     */
    public static function getReverseRelationType($type)
    {
        switch ($type) {
            case RelationType::ONE_TO_MANY:
                return RelationType::MANY_TO_ONE;
            case RelationType::MANY_TO_ONE:
                return RelationType::ONE_TO_MANY;
            case RelationType::MANY_TO_MANY:
                return RelationType::MANY_TO_MANY;
            default:
                return $type;
        }
    }

    /**
     * Returns a string that can be used as a field name for inverse side of to-many relation.
     *
     * @param string $entityClassName The FQCN of owning side entity
     * @param string $fieldName       The name of owning side field
     *
     * @return string
     */
    public static function buildToManyRelationTargetFieldName($entityClassName, $fieldName)
    {
        return strtolower(self::getShortClassName($entityClassName)) . '_' . $fieldName;
    }

    /**
     * Returns a string that can be used as a field name to the relation to the given entity.
     *
     * To prevent name collisions this method adds a hash built based on the full class name
     * and the kind of the association to the end.
     *
     * @param string $targetEntityClassName The association target class name
     * @param string $associationKind       The kind of the association
     *                                      For example 'activity', 'sponsorship' etc
     *                                      NULL for unclassified (default) association
     *
     * @return string
     */
    public static function buildAssociationName($targetEntityClassName, $associationKind = null)
    {
        $hash = strtolower(
            dechex(
                crc32(
                    empty($associationKind) ? $targetEntityClassName : $associationKind . $targetEntityClassName
                )
            )
        );

        return sprintf(
            '%s_%s',
            Inflector::tableize(self::getShortClassName($targetEntityClassName)),
            $hash
        );
    }

    /**
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $relationType
     * @param string $targetEntityClassName
     *
     * @return string
     */
    public static function buildRelationKey($entityClassName, $fieldName, $relationType, $targetEntityClassName)
    {
        return implode('|', [$relationType, $entityClassName, $targetEntityClassName, $fieldName]);
    }

    /**
     * @param string $relationKey
     *
     * @return string
     */
    public static function getRelationType($relationKey)
    {
        $parts = explode('|', $relationKey);

        return reset($parts);
    }

    /**
     * Returns an enum identifier based on the given enum name.
     *
     * @param string $enumName
     * @param bool   $throwExceptionIfInvalidName
     *
     * @return string The enum code. Can be empty string if $throwExceptionIfInvalidName = false
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumCode($enumName, $throwExceptionIfInvalidName = true)
    {
        if (empty($enumName)) {
            if (!$throwExceptionIfInvalidName) {
                return '';
            }

            throw new \InvalidArgumentException('$enumName must not be empty.');
        }

        $tr = \Transliterator::create('Latin; Latin-ASCII; Lower');
        $enumName = $tr->transliterate($enumName);

        $result = preg_replace(
            ['/ +/', '/-+/', '/[^a-z0-9_]+/i', '/_{2,}/'],
            ['_', '_', '', '_'],
            trim($enumName)
        );
        if ($result === '_') {
            $result = '';
        }

        if (empty($result) && $throwExceptionIfInvalidName) {
            throw new \InvalidArgumentException(
                sprintf('The conversion of "%s" to enum code produces empty string.', $enumName)
            );
        }

        return $result;
    }

    /**
     * Generates an enum identifier based on the given entity class and field.
     * This method can be used if there is no enum name and as result
     * {@link buildEnumCode()} method cannot be used.
     *
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $maxEnumCodeSize
     *
     * @return string The enum code.
     *
     * @throws \InvalidArgumentException
     */
    public static function generateEnumCode($entityClassName, $fieldName, $maxEnumCodeSize = null)
    {
        if (empty($entityClassName)) {
            throw new \InvalidArgumentException('$entityClassName must not be empty.');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty.');
        }
        if (null !== $maxEnumCodeSize && $maxEnumCodeSize < 21) {
            throw new \InvalidArgumentException('$maxEnumCodeSize must be greater or equal than 21 chars.');
        }

        $shortClassName = self::getShortClassName($entityClassName);

        $enumCode = sprintf(
            '%s_%s_%s',
            Inflector::tableize($shortClassName),
            Inflector::tableize($fieldName),
            dechex(crc32($entityClassName . '::' . $fieldName))
        );

        if (null !== $maxEnumCodeSize && strlen($enumCode) > $maxEnumCodeSize) {
            $enumCode = sprintf(
                '%s_%s',
                Inflector::tableize($shortClassName),
                dechex(crc32($entityClassName . '::' . $fieldName))
            );
            if (strlen($enumCode) > $maxEnumCodeSize) {
                $enumCode = sprintf(
                    'enum_%s_%s',
                    dechex(crc32($shortClassName)),
                    dechex(crc32($entityClassName . '::' . $fieldName))
                );
            }
        }

        return $enumCode;
    }

    /**
     * Returns an enum value identifier based on the given value name.
     *
     * @param string $enumValueName
     * @param bool   $throwExceptionIfInvalidName
     *
     * @return string The enum value identifier. Can be empty string if $throwExceptionIfInvalidName = false
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumValueId($enumValueName, $throwExceptionIfInvalidName = true)
    {
        if (strlen($enumValueName) === 0) {
            if (!$throwExceptionIfInvalidName) {
                return '';
            }

            throw new \InvalidArgumentException('$enumValueName must not be empty.');
        }

        $tr = \Transliterator::create('Latin; Latin-ASCII; Lower');
        if ($tr) {
            $enumValueName = $tr->transliterate($enumValueName);
        }

        $result = preg_replace(
            ['/ +/', '/-+/', '/[^a-z0-9_]+/i', '/_{2,}/'],
            ['_', '_', '', '_'],
            trim($enumValueName)
        );
        if ($result === '_') {
            $result = '';
        }

        if (strlen($result) > self::MAX_ENUM_VALUE_ID_LENGTH) {
            $hash   = dechex(crc32($result));
            $result = substr($result, 0, self::MAX_ENUM_VALUE_ID_LENGTH - strlen($hash) - 1) . '_' . $hash;
        }

        if ($throwExceptionIfInvalidName && strlen($result) === 0) {
            throw new \InvalidArgumentException(
                sprintf('The conversion of "%s" to enum value id produces empty string.', $enumValueName)
            );
        }

        return $result;
    }

    /**
     * Returns full class name for an entity is used to store values of the given enum.
     *
     * @param string $enumCode
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumValueClassName($enumCode)
    {
        if (empty($enumCode)) {
            throw new \InvalidArgumentException('$enumCode must not be empty.');
        }

        return self::ENTITY_NAMESPACE . 'EV_' . str_replace(' ', '_', ucwords(strtr($enumCode, '_-', '  ')));
    }

    /**
     * Returns the name of a field that is used to store selected options for multiple enums
     * This field is required to avoid group by clause when multiple enum is shown in a datagrid
     *
     * @param string $fieldName The field name that is a reference to enum values table
     *
     * @return string
     */
    public static function getMultiEnumSnapshotFieldName($fieldName)
    {
        return $fieldName . self::ENUM_SNAPSHOT_SUFFIX;
    }

    /**
     * Returns a translation key (placeholder) for entities responsible to store enum values
     *
     * @param string $propertyName property key: label, description, plural_label, etc.
     * @param string $enumCode
     * @param string $fieldName
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getEnumTranslationKey($propertyName, $enumCode, $fieldName = null)
    {
        if (empty($propertyName)) {
            throw new \InvalidArgumentException('$propertyName must not be empty');
        }
        if (empty($enumCode)) {
            throw new \InvalidArgumentException('$enumCode must not be empty');
        }

        if (!$fieldName) {
            return sprintf('oro.entityextend.enums.%s.entity_%s', $enumCode, $propertyName);
        }

        return sprintf('oro.entityextend.enumvalue.%s.%s', $fieldName, $propertyName);
    }

    /**
     * Checks if an entity is a custom one
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @param string $className
     *
     * @return bool
     */
    public static function isCustomEntity($className)
    {
        return strpos($className, self::ENTITY_NAMESPACE) === 0;
    }

    /**
     * Checks if the given class is a proxy for extend entity
     *
     * @param string $className
     *
     * @return bool
     */
    public static function isExtendEntityProxy($className)
    {
        return strpos($className, self::ENTITY_NAMESPACE) === 0;
    }

    /**
     * Gets the short name of the class, the part without the namespace.
     *
     * @param string $className The full name of a class
     *
     * @return string
     */
    public static function getShortClassName($className)
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }

    /**
     * Returns full class name of a proxy class for extendable entity.
     *
     * @param string $extendClassName The full class name of a parent class for extendable entity
     *
     * @return string
     */
    public static function getExtendEntityProxyClassName($extendClassName)
    {
        $parts = explode('\\', $extendClassName);
        $shortClassName = array_pop($parts);
        if (strpos($shortClassName, 'Extend') === 0) {
            $shortClassName = substr($shortClassName, 6);
        }
        $proxyShortClassName = 'EX_' . array_shift($parts);
        $nameParts = [];
        foreach ($parts as $item) {
            if ($item === 'Bundle' || $item === 'Model') {
                continue;
            }
            if (!isset($nameParts[$item])) {
                $nameParts[$item] = true;
                $proxyShortClassName .= $item . '_';
            }
        }
        $proxyShortClassName .= $shortClassName;

        return self::ENTITY_NAMESPACE . $proxyShortClassName;
    }

    /**
     * Check if the given configurable entity is ready to be used in a business logic.
     * It means that a entity class should exist and should not be marked as deleted.
     *
     * @param ConfigInterface $extendConfig The entity's configuration in the 'extend' scope
     *
     * @return bool
     */
    public static function isEntityAccessible(ConfigInterface $extendConfig)
    {
        if ($extendConfig->is('is_extend')) {
            if ($extendConfig->is('is_deleted')) {
                return false;
            }
            if ($extendConfig->is('state', ExtendScope::STATE_NEW)) {
                return false;
            }
            // check if a new entity has been requested to be deleted before schema is updated
            if ($extendConfig->is('state', ExtendScope::STATE_DELETE)
                && !class_exists($extendConfig->getId()->getClassName())
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the given configurable entity is used to store enum values and ready to be used in a business logic.
     * It means that a entity class should be extended from AbstractEnumValue,
     * should exist and should not be marked as deleted.
     *
     * @param ConfigInterface $extendConfig The entity's configuration in the 'extend' scope
     *
     * @return bool
     */
    public static function isEnumValueEntityAccessible(ConfigInterface $extendConfig)
    {
        return
            $extendConfig->is('is_extend')
            && $extendConfig->is('inherit', self::BASE_ENUM_VALUE_CLASS)
            && self::isEntityAccessible($extendConfig);
    }

    /**
     * Check if the given configurable field is ready to be used in a business logic.
     * It means that a field should exist in a class and should not be marked as deleted.
     *
     * @param ConfigInterface $extendFieldConfig The field's configuration in the 'extend' scope
     *
     * @return bool
     */
    public static function isFieldAccessible(ConfigInterface $extendFieldConfig)
    {
        if ($extendFieldConfig->is('is_extend')) {
            if ($extendFieldConfig->is('is_deleted')) {
                return false;
            }
            if ($extendFieldConfig->is('state', ExtendScope::STATE_NEW)) {
                return false;
            }
            // check if a new field has been requested to be deleted before schema is updated
            if ($extendFieldConfig->is('state', ExtendScope::STATE_DELETE)) {
                /** @var FieldConfigId $fieldId */
                $fieldId = $extendFieldConfig->getId();
                if (!property_exists($fieldId->getClassName(), $fieldId->getFieldName())) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param mixed $currentVal
     * @param array $changeSet
     *
     * @return mixed
     */
    public static function updatedPendingValue($currentVal, array $changeSet)
    {
        list ($oldVal, $newVal) = $changeSet;
        if (!is_array($oldVal) || !is_array($newVal) || !is_array($currentVal)) {
            return $newVal;
        }

        return array_values(
            array_diff(
                array_merge($currentVal, array_diff($newVal, $oldVal)),
                array_diff($oldVal, $newVal)
            )
        );
    }
}
