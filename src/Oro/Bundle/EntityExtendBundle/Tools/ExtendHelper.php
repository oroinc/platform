<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;

/**
 * Provides utility static methods to work with extended entities.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendHelper
{
    public const string ENTITY_NAMESPACE = 'Extend\\Entity\\';
    public const string ENUM_CLASS_NAME_PREFIX = self::ENTITY_NAMESPACE . 'EV_';
    public const string ENUM_TRANSLATION_PREFIX = 'oro.entity_extend.enum_option.';
    public const int MAX_ENUM_ID_LENGTH = 100;
    public const int MAX_ENUM_INTERNAL_ID_LENGTH = 32;
    public const int MAX_ENUM_SNAPSHOT_LENGTH = 500;
    public const string ENUM_OPTION_SEPARATOR = '.';
    public const string ENUM_SNAPSHOT_SUFFIX = 'Snapshot'; // Outdated enum snapshot suffix

    public static function getReverseRelationType(string $type): string
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
    public static function buildToManyRelationTargetFieldName(string $entityClassName, string $fieldName): string
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
            InflectorFactory::create()->tableize(self::getShortClassName($targetEntityClassName)),
            $hash
        );
    }

    /**
     * Returns a relation key used for extended relations.
     * The result string is "relationType|entityClassName|targetEntityClassName|fieldName".
     */
    public static function buildRelationKey(
        string $entityClassName,
        string $fieldName,
        string $relationType,
        string $targetEntityClassName
    ): string {
        return implode('|', [$relationType, $entityClassName, $targetEntityClassName, $fieldName]);
    }

    /**
     * Returns a relation key for inverse side relation if the given relation key represents owning side relation,
     * and vice versa.
     * A valid relation key is
     * either "relationType|entityClassName|targetEntityClassName|fieldName"
     * or "relationType|entityClassName|targetEntityClassName|fieldName|inverse".
     *
     * @param string $relationKey
     *
     * @return string|null
     */
    public static function toggleRelationKey($relationKey)
    {
        $parts = explode('|', $relationKey);
        // toggle the relation key only if owning side entity equals to inverse side entity
        $numberOfParts = count($parts);
        if ($numberOfParts >= 4 && $parts[1] === $parts[2]) {
            if (4 === $numberOfParts) {
                $relationKey .= '|inverse';
            } elseif (5 === $numberOfParts && 'inverse' === $parts[4]) {
                $relationKey = substr($relationKey, 0, -8);
            }
        }

        return $relationKey;
    }

    /**
     * Extracts an extended relation type from its relation key.
     * A valid relation key is
     * either "relationType|entityClassName|targetEntityClassName|fieldName"
     * or "relationType|entityClassName|targetEntityClassName|fieldName|inverse".
     *
     * @param string $relationKey
     *
     * @return string|null
     */
    public static function getRelationType($relationKey)
    {
        if ($relationKey === null) {
            return null;
        }
        $parts = explode('|', $relationKey);
        $numberOfParts = count($parts);
        if ($numberOfParts < 4 || $numberOfParts > 5) {
            return null;
        }

        return reset($parts);
    }

    /**
     * Returns an enum identifier based on the given enum name.
     * The return value can be empty string if $throwExceptionIfInvalidName = false.
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumCode(string $enumName, bool $throwExceptionIfInvalidName = true): string
    {
        if ('' === $enumName) {
            if ($throwExceptionIfInvalidName) {
                throw new \InvalidArgumentException('The enum name must not be empty.');
            }

            return '';
        }

        $result = self::convertEnumNameToCode($enumName);
        if ($throwExceptionIfInvalidName && '' === $result) {
            throw new \InvalidArgumentException(sprintf(
                'The conversion of "%s" to enum code produces empty string.',
                $enumName
            ));
        }

        return $result;
    }

    public static function isEnumerableType(string $type): bool
    {
        return self::isSingleEnumType($type) || self::isMultiEnumType($type);
    }

    public static function isSingleEnumType(string $type): bool
    {
        return $type === 'enum';
    }

    public static function isMultiEnumType(string $type): bool
    {
        return $type === 'multiEnum';
    }

    public static function buildEnumOptionTranslationKey(string $enumOptionId): string
    {
        return self::ENUM_TRANSLATION_PREFIX . $enumOptionId;
    }

    public static function getEnumOptionIdFromTranslationKey(string $translationKey): string
    {
        if (!str_contains($translationKey, self::ENUM_TRANSLATION_PREFIX)) {
            throw new \InvalidArgumentException(sprintf(
                'Wrong translation key passed "%s" it should contains "%s" as part of itself.',
                self::ENUM_TRANSLATION_PREFIX
            ));
        }

        return str_replace(self::ENUM_TRANSLATION_PREFIX, '', $translationKey);
    }

    public static function buildEnumOptionId(string $enumCode, string $internalId): string
    {
        return $enumCode . self::ENUM_OPTION_SEPARATOR . $internalId;
    }

    public static function extractEnumCode(string $enumOptionId): string
    {
        $explodedParts = explode(self::ENUM_OPTION_SEPARATOR, $enumOptionId);
        if (!is_array($explodedParts) && count($explodedParts) !== 2) {
            throw new \LogicException('Input enum options id is broken or has invalid format');
        }

        return reset($explodedParts);
    }

    /**
     * Generates an enum identifier based on the given entity class and field.
     * This method can be used if there is no enum name and as result
     * {@link buildEnumCode()} method cannot be used.
     *
     * @throws \InvalidArgumentException
     */
    public static function generateEnumCode(
        string $entityClassName,
        string $fieldName,
        ?int $maxEnumCodeSize = null
    ): string {
        if ('' === $entityClassName) {
            throw new \InvalidArgumentException('$entityClassName must not be empty.');
        }
        if ('' === $fieldName) {
            throw new \InvalidArgumentException('$fieldName must not be empty.');
        }
        if (null !== $maxEnumCodeSize && $maxEnumCodeSize < 21) {
            throw new \InvalidArgumentException('$maxEnumCodeSize must be greater or equal than 21 chars.');
        }

        $shortClassName = self::getShortClassName($entityClassName);

        $enumCode = sprintf(
            '%s_%s_%s',
            InflectorFactory::create()->tableize($shortClassName),
            InflectorFactory::create()->tableize($fieldName),
            dechex(crc32($entityClassName . '::' . $fieldName))
        );

        if (null !== $maxEnumCodeSize && strlen($enumCode) > $maxEnumCodeSize) {
            $enumCode = sprintf(
                '%s_%s',
                InflectorFactory::create()->tableize($shortClassName),
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
     * The return value can be empty string if $throwExceptionIfInvalidName = false.
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumInternalId(string $enumValueName, bool $throwExceptionIfInvalidName = true): string
    {
        if ($enumValueName === '') {
            if ($throwExceptionIfInvalidName) {
                throw new \InvalidArgumentException('The enum value name must not be empty.');
            }

            return '';
        }

        $result = self::convertEnumNameToCode($enumValueName);
        if (strlen($result) > self::MAX_ENUM_INTERNAL_ID_LENGTH) {
            $hash = dechex(crc32($result));
            $result = substr($result, 0, self::MAX_ENUM_INTERNAL_ID_LENGTH - strlen($hash) - 1) . '_' . $hash;
        }
        if ($throwExceptionIfInvalidName && '' === $result) {
            throw new \InvalidArgumentException(sprintf(
                'The conversion of "%s" to enum value id produces empty string.',
                $enumValueName
            ));
        }

        return $result;
    }

    /**
     * @param string $enumOptionId format: "enum_code.internal_id"
     */
    public static function getEnumInternalId(string $enumOptionId): string
    {
        if (self::isInternalEnumId($enumOptionId)) {
            return $enumOptionId;
        }

        return substr($enumOptionId, strpos($enumOptionId, '.') + 1);
    }

    public static function isInternalEnumId(string $value): bool
    {
        return !str_contains($value, '.');
    }

    /**
     * Converts enum name to enum code
     *
     * @param string $name
     *
     * @return string
     */
    private static function convertEnumNameToCode($name)
    {
        if ($name && function_exists('iconv')) {
            $locale = setlocale(LC_CTYPE, 0);
            if ('C' === $locale || false === $locale) {
                $transliteratedName = @iconv('utf-8', 'ascii//TRANSLIT', $name);
            } else {
                setlocale(LC_CTYPE, 'C');
                $transliteratedName = @iconv('utf-8', 'ascii//TRANSLIT', $name);
                setlocale(LC_CTYPE, $locale);
            }
            if (false === $transliteratedName) {
                throw new \RuntimeException(sprintf(
                    "Can't convert the string '%s' with the 'iconv' function. " .
                    "Please check that the 'iconv' extension is configured correctly.",
                    $name
                ));
            }
            if (str_contains($transliteratedName, '?')) {
                $name = hash('crc32', $name);
            } else {
                $name = $transliteratedName;
            }
        }

        $result = strtolower(
            preg_replace(
                ['/ +/', '/-+/', '/[^a-z0-9\_]+/i', '/_{2,}/'],
                ['_', '_', '', '_'],
                trim($name)
            )
        );
        if ($result === '_') {
            $result = '';
        }

        return $result;
    }

    /**
     * Checks if the given string is a virtual class for an enum option entity.
     */
    public static function isOutdatedEnumOptionEntity(string $className): bool
    {
        return str_starts_with($className, self::ENUM_CLASS_NAME_PREFIX);
    }

    /**
     * Gets a virtual class of an enum option entity for the given enum code.
     */
    public static function getOutdatedEnumOptionClassName(string $enumCode): string
    {
        return self::ENUM_CLASS_NAME_PREFIX
            . str_replace(' ', '_', ucwords(str_replace('_', ' ', $enumCode)));
    }

    /**
     * Gets an enum identifier for the given virtual class for an enum option entity.
     */
    public static function getEnumCode(string $enumOptionEntityClassName): string
    {
        return strtolower(substr($enumOptionEntityClassName, \strlen(self::ENUM_CLASS_NAME_PREFIX)));
    }

    public static function mapToEnumOptionIds(string $enumCode, array $enumInternalIds): array
    {
        return array_map(
            fn ($internalId) => self::buildEnumOptionId($enumCode, $internalId),
            $enumInternalIds
        );
    }

    /**
     * @param array $dataWithEnumKeys ['enum_internal_id' => 'mixed_value']
     */
    public static function mapKeysToEnumOptionIds(array $dataWithEnumKeys, string $enumCode): array
    {
        $mappedToOptionIdData = [];
        foreach ($dataWithEnumKeys as $internalId => $value) {
            $mappedToOptionIdData[ExtendHelper::buildEnumOptionId($enumCode, $internalId)] = $value;
        }

        return $mappedToOptionIdData;
    }

    public static function mapToEnumInternalIds(array $enumOptionIds): array
    {
        return array_map(
            fn ($optionId) => self::getEnumInternalId($optionId),
            $enumOptionIds
        );
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

    public static function getEnumTranslationKey(
        string $propertyName,
        string $enumCode = '',
        ?string $fieldName = null
    ): string {
        if ('' === $propertyName) {
            throw new \InvalidArgumentException('$propertyName must not be empty');
        }

        if (!$fieldName) {
            return sprintf('oro.entityextend.enums.%s.entity_%s', $enumCode, $propertyName);
        }

        return sprintf('oro.entityextend.enumvalue.%s.%s', $fieldName, $propertyName);
    }

    public static function isExtendEntity(object|string $class): bool
    {
        return is_subclass_of($class, ExtendEntityInterface::class);
    }

    /**
     * Checks if an entity is a custom one
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     */
    public static function isCustomEntity(string $className): bool
    {
        return str_starts_with($className, self::ENTITY_NAMESPACE);
    }

    /**
     * Checks if the given class is a proxy for extend entity
     */
    public static function isExtendEntityProxy(string $className): bool
    {
        return str_starts_with($className, self::ENTITY_NAMESPACE);
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
     * Gets a parent class for the given class.
     */
    public static function getParentClassName(string $className): ?string
    {
        if (self::isOutdatedEnumOptionEntity($className)) {
            return EnumOption::class;
        }

        $parentClass = (new \ReflectionClass($className))->getParentClass();
        if (!$parentClass) {
            return null;
        }

        return $parentClass->getName();
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
        if (str_starts_with($shortClassName, 'Extend')) {
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
            $state = $extendConfig->get('state');
            if (ExtendScope::STATE_NEW === $state) {
                return false;
            }
            // check if a new entity has been requested to be deleted before schema is updated
            if (ExtendScope::STATE_DELETE === $state
                && !class_exists($extendConfig->getId()->getClassName())
            ) {
                return false;
            }
        }

        return true;
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
            $state = $extendFieldConfig->get('state');
            if (ExtendScope::STATE_NEW === $state) {
                return false;
            }
            // check if a new field has been requested to be deleted before schema is updated
            if (ExtendScope::STATE_DELETE === $state) {
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
        [$oldVal, $newVal] = $changeSet;
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
