<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\Inflector\Inflector;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * A generic provider for entity text representation:
 * - Short format returns name based on guesses using first existing field from the list $fieldGuesses or false
 * if no appropriate field is found
 * - Full format returns concatenation of all string fields or false if no string fields are found.
 * When existing fields have empty values, identifier (if exists and is single) will be used as title.
 */
class EntityNameProvider implements EntityNameProviderInterface
{
    /** @var string[] */
    private array $fieldGuesses;
    private ManagerRegistry $doctrine;
    private ConfigProvider $configProvider;
    private Inflector $inflector;

    public function __construct(
        array $fieldGuesses,
        ManagerRegistry $doctrine,
        ConfigProvider $configProvider,
        Inflector $inflector
    ) {
        $this->fieldGuesses = $fieldGuesses;
        $this->doctrine = $doctrine;
        $this->configProvider = $configProvider;
        $this->inflector = $inflector;
    }

    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$this->isFormatSupported($format)) {
            return false;
        }

        $className = ClassUtils::getClass($entity);

        $fieldNames = self::FULL === $format
            ? $this->getFieldNames($className)
            : (array)$this->guessFieldName($className);
        if (empty($fieldNames)) {
            // no suitable fields
            return false;
        }

        $name = $this->getConstructedName($entity, $fieldNames);
        if ($name) {
            return $name;
        }

        // field value is empty, try with id
        $idFiledName = $this->getSingleIdFieldName($className);
        if ($idFiledName) {
            $idValue = $this->getFieldValue($entity, $idFiledName);

            return null === $idValue || \is_string($idValue)
                ? $idValue
                : (string)$idValue;
        }

        // no identifier column
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!$this->isFormatSupported($format)) {
            return false;
        }

        if (self::SHORT === $format) {
            $guessFieldName = $this->guessFieldName($className);
            if (!$guessFieldName) {
                return false;
            }

            return $this->addIdFallback($alias . '.' . $guessFieldName, $alias, $className);
        }

        $fieldNames = $this->getFieldNames($className);
        if (!$fieldNames) {
            return false;
        }

        $fieldNames = array_map(
            function ($fieldName) use ($alias) {
                return $alias . '.' . $fieldName;
            },
            $fieldNames
        );

        $nameDQL = reset($fieldNames);
        if (\count($fieldNames) > 1) {
            $nameDQL = sprintf("CONCAT_WS(' ', %s)", implode(', ', $fieldNames));
        }

        return $this->addIdFallback($nameDQL, $alias, $className);
    }

    private function isFormatSupported(string $format): bool
    {
        return self::FULL === $format || self::SHORT === $format;
    }

    private function getSingleIdFieldName(string $className): ?string
    {
        $metadata = $this->getClassMetadata($className);
        if (!$metadata) {
            return null;
        }

        $identifierFieldNames = $metadata->getIdentifierFieldNames();
        if (\count($identifierFieldNames) !== 1) {
            return null;
        }

        return reset($identifierFieldNames);
    }

    private function addIdFallback(string $nameDQL, string $alias, string $className): string
    {
        $idFieldName = $this->getSingleIdFieldName($className);
        if (null === $idFieldName) {
            return $nameDQL;
        }

        // use cast to avoid mixed collation errors
        return sprintf('COALESCE(CAST(%s AS string), CAST(%s AS string))', $nameDQL, $alias . '.' . $idFieldName);
    }

    private function getClassMetadata(string $className): ?ClassMetadata
    {
        return $this->doctrine->getManagerForClass($className)?->getClassMetadata($className);
    }

    private function guessFieldName(string $className): ?string
    {
        $metadata = $this->getClassMetadata($className);
        if (null === $metadata) {
            return null;
        }

        foreach ($this->fieldGuesses as $fieldName) {
            if ($this->isFieldSupported($metadata, $fieldName)) {
                return $fieldName;
            }
        }

        return null;
    }

    private function getFieldValue(object $entity, string $fieldName): mixed
    {
        $getterName = 'get' . $this->inflector->classify($fieldName);

        if (EntityPropertyInfo::methodExists($entity, $getterName)) {
            $value = $entity->{$getterName}();
        } else {
            $value = $entity->{$fieldName} ?? null;
        }

        if ($value instanceof \UnitEnum) {
            $value = $value->value;
        }

        return $value;
    }

    private function getFieldNames(string $className): array
    {
        $metadata = $this->getClassMetadata($className);
        if (null === $metadata) {
            return [];
        }

        $result = [];
        foreach ($metadata->getFieldNames() as $fieldName) {
            if ($this->isFieldSupported($metadata, $fieldName)) {
                $result[] = $fieldName;
            }
        }

        return $result;
    }

    private function getConstructedName(object $entity, array $fieldNames): ?string
    {
        $values = [];
        foreach ($fieldNames as $field) {
            $values[] = $this->getFieldValue($entity, $field);
        }

        $values = array_filter($values);

        return empty($values) ? null : implode(' ', $values);
    }

    private function isFieldSupported(ClassMetadata $metadata, string $fieldName): bool
    {
        if (!$metadata->hasField($fieldName)) {
            return false;
        }
        if ($metadata->getTypeOfField($fieldName) !== Types::STRING) {
            return false;
        }
        if ($this->configProvider->hasConfig($metadata->getName(), $fieldName)
            && !ExtendHelper::isFieldAccessible($this->configProvider->getConfig($metadata->getName(), $fieldName))
        ) {
            return false;
        }

        return true;
    }
}
