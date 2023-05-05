<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * Provides a set of methods to simplify working with entity identifier.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityIdHelper
{
    /**
     * Sets the identifier value to a given entity.
     *
     * @throws \InvalidArgumentException
     */
    public function setEntityIdentifier(object $entity, mixed $entityId, EntityIdMetadataInterface $metadata): void
    {
        if (!\is_array($entityId)) {
            $idFieldNames = $metadata->getIdentifierFieldNames();
            if (\count($idFieldNames) > 1) {
                throw new \InvalidArgumentException(sprintf(
                    'Unexpected identifier value "%s" for composite identifier of the entity "%s".',
                    $entityId,
                    $metadata->getClassName()
                ));
            }
            $entityId = [reset($idFieldNames) => $entityId];
        }

        $reflClass = new EntityReflectionClass($entity);
        foreach ($entityId as $fieldName => $value) {
            $propertyName = $metadata->getPropertyPath($fieldName);
            if (null === $propertyName) {
                throw new \InvalidArgumentException(sprintf(
                    'The entity "%s" does not have metadata for the "%s" property.',
                    \get_class($entity),
                    $fieldName
                ));
            }
            $setter = $this->getSetter($reflClass, $propertyName);
            if (null !== $setter) {
                $setter->invoke($entity, $value);
            } else {
                $property = ReflectionUtil::getProperty($reflClass, $propertyName);
                if (null === $property) {
                    throw new \InvalidArgumentException(sprintf(
                        'The entity "%s" does not have the "%s" property.',
                        \get_class($entity),
                        $propertyName
                    ));
                }

                if (!$property->isPublic()) {
                    $property->setAccessible(true);
                }
                $property->setValue($entity, $value);
            }
        }
    }

    /**
     * Adds a restriction by the entity identifier to the given query builder.
     */
    public function applyEntityIdentifierRestriction(
        QueryBuilder $qb,
        mixed $entityId,
        EntityIdMetadataInterface $metadata,
        string $entityAlias = null,
        string $idParamName = null
    ): void {
        if (!$entityAlias) {
            $entityAlias = QueryBuilderUtil::getSingleRootAlias($qb);
        }
        if (!$idParamName) {
            $idParamName = 'id';
        }
        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (\count($idFieldNames) === 1) {
            // single identifier
            if (\is_array($entityId)) {
                throw new RuntimeException(sprintf(
                    'The entity identifier cannot be an array because the entity "%s" has single identifier.',
                    $metadata->getClassName()
                ));
            }
            $propertyName = $metadata->getPropertyPath(reset($idFieldNames));
            $qb
                ->andWhere(sprintf('%s.%s = :%s', $entityAlias, $propertyName, $idParamName))
                ->setParameter($idParamName, $entityId);
        } else {
            // composite identifier
            if (!\is_array($entityId)) {
                throw new RuntimeException(sprintf(
                    'The entity identifier must be an array because the entity "%s" has composite identifier.',
                    $metadata->getClassName()
                ));
            }
            $counter = 1;
            foreach ($idFieldNames as $fieldName) {
                if (!\array_key_exists($fieldName, $entityId)) {
                    throw new RuntimeException(sprintf(
                        'The entity identifier array must have the key "%s" because '
                        . 'the entity "%s" has composite identifier.',
                        $fieldName,
                        $metadata->getClassName()
                    ));
                }
                $propertyName = $metadata->getPropertyPath($fieldName);
                $qb
                    ->andWhere(sprintf('%s.%s = :%s%d', $entityAlias, $propertyName, $idParamName, $counter))
                    ->setParameter(sprintf('%s%d', $idParamName, $counter), $entityId[$fieldName]);
                $counter++;
            }
        }
    }

    /**
     * Checks whether the given entity identifier fields are equal to
     * an entity identifier fields from the given entity configuration.
     *
     * @param string[]               $identifierFieldNames
     * @param EntityDefinitionConfig $config
     *
     * @return bool
     */
    public function isEntityIdentifierEqual(array $identifierFieldNames, EntityDefinitionConfig $config): bool
    {
        $configuredIdFieldNames = $config->getIdentifierFieldNames();
        if (empty($configuredIdFieldNames)) {
            return false;
        }

        $isEqual = true;
        foreach ($configuredIdFieldNames as $fieldName) {
            $field = $config->getField($fieldName);
            if (null !== $field) {
                $fieldName = $field->getPropertyPath($fieldName);
            }
            if (null !== $field && !\in_array($fieldName, $identifierFieldNames, true)) {
                $isEqual = false;
                break;
            }
        }

        return $isEqual;
    }

    /**
     * Checks whether the given entity identifiers are equal.
     */
    public function areEntityIdentifiersEqual(mixed $identifier1, mixed $identifier2): bool
    {
        if (\is_array($identifier1)) {
            if (!\is_array($identifier2) || \count($identifier2) !== \count($identifier1)) {
                return false;
            }

            $isEqual = true;
            ksort($identifier1);
            ksort($identifier2);
            foreach ($identifier1 as $fieldName => $value) {
                if (!\array_key_exists($fieldName, $identifier2) || $identifier2[$fieldName] != $value) {
                    $isEqual = false;
                    break;
                }
            }

            return $isEqual;
        }
        if (\is_array($identifier2)) {
            return false;
        }

        return $identifier1 == $identifier2;
    }

    /**
     * Gets an identifier of the given entity.
     *
     * @param object                    $entity
     * @param EntityIdMetadataInterface $metadata
     *
     * @return mixed A scalar value if the entity has a single identifier,
     *               or an array [field name => value, ...] if the entity has a composite identifier
     */
    public function getEntityIdentifier(object $entity, EntityIdMetadataInterface $metadata): mixed
    {
        $idFieldNames = [];
        foreach ($metadata->getIdentifierFieldNames() as $fieldName) {
            $propertyName = $metadata->getPropertyPath($fieldName);
            if (null === $propertyName) {
                $propertyName = $fieldName;
            }
            $idFieldNames[] = $propertyName;
        }

        $identifier = [];
        $reflClass = new EntityReflectionClass($entity);
        foreach ($idFieldNames as $fieldName) {
            $getter = $this->getGetter($reflClass, $fieldName);
            if (null !== $getter) {
                $identifier[$fieldName] = $getter->invoke($entity);
            } else {
                $property = ReflectionUtil::getProperty($reflClass, $fieldName);
                if (null === $property) {
                    throw new \InvalidArgumentException(sprintf(
                        'The entity "%s" does not have the "%s" property.',
                        \get_class($entity),
                        $fieldName
                    ));
                }
                if (!$property->isPublic()) {
                    $property->setAccessible(true);
                }
                $identifier[$fieldName] = $property->getValue($entity);
            }
        }

        if (\count($identifier) === 1) {
            $identifier = reset($identifier);
        }

        return $identifier;
    }

    /**
     * Checks whether the given entity identifier is empty.
     * A single valued identifier is empty if its value equals to NULL.
     * A composite identifier is empty if values of all its fields equal to NULL.
     */
    public function isEntityIdentifierEmpty(mixed $identifier): bool
    {
        if (null === $identifier) {
            return true;
        }
        if (!\is_array($identifier)) {
            return false;
        }

        $isEmpty = true;
        foreach ($identifier as $value) {
            if (null !== $value) {
                $isEmpty = false;
                break;
            }
        }

        return $isEmpty;
    }

    /**
     * Gets a public getter for the given property.
     */
    private function getGetter(\ReflectionClass $reflClass, string $propertyName): ?\ReflectionMethod
    {
        $getterName = 'get' . $this->camelize($propertyName);
        if ($reflClass->hasMethod($getterName)) {
            $getter = $reflClass->getMethod($getterName);
            if ($getter->isPublic() && $getter->getNumberOfRequiredParameters() === 0) {
                return $getter;
            }
        }

        return null;
    }

    /**
     * Gets a public setter for the given property.
     */
    private function getSetter(\ReflectionClass $reflClass, string $propertyName): ?\ReflectionMethod
    {
        $setterName = 'set' . $this->camelize($propertyName);
        if ($reflClass->hasMethod($setterName)) {
            $setter = $reflClass->getMethod($setterName);
            if ($setter->isPublic()
                && $setter->getNumberOfParameters() > 0
                && $setter->getNumberOfRequiredParameters() <= 1
            ) {
                return $setter;
            }
        }

        return null;
    }

    /**
     * Camelizes a given string.
     */
    private function camelize(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace('.', ' ', str_replace('_', ' ', $value))));
    }
}
