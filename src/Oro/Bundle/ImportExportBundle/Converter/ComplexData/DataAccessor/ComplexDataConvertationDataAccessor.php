<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides an access to data during complex data import and export.
 */
class ComplexDataConvertationDataAccessor implements ComplexDataConvertationDataAccessorInterface
{
    private const string ID = 'id';
    private const string NAME = 'name';
    private const string INTERNAL_ID = 'internalId';
    private const string ENUM_CODE = 'enumCode';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly ComplexDataConvertationEntityLoaderInterface $entityLoader,
        private readonly EnumOptionsProvider $enumOptionsProvider
    ) {
    }

    #[\Override]
    public function getFieldValue(object $entity, string $propertyPath): mixed
    {
        if (!$this->propertyAccessor->isReadable($entity, $propertyPath)) {
            return null;
        }

        return $this->propertyAccessor->getValue($entity, $propertyPath);
    }

    #[\Override]
    public function getLookupFieldValue(object $entity, ?string $lookupFieldName, ?string $entityClass): mixed
    {
        if ($entityClass && ExtendHelper::isOutdatedEnumOptionEntity($entityClass)) {
            return $this->getEnumLookupFieldValue($entity, $lookupFieldName, $entityClass);
        }

        return $this->getFieldValue($entity, $this->resolveLookupFieldName($lookupFieldName, $entityClass));
    }

    #[\Override]
    public function findEntityId(string $entityClass, ?string $lookupFieldName, mixed $lookupFieldValue): mixed
    {
        if (ExtendHelper::isOutdatedEnumOptionEntity($entityClass)) {
            return $this->findEnumEntityId($entityClass, $lookupFieldName, $lookupFieldValue);
        }

        $entity = $this->entityLoader->loadEntity(
            $entityClass,
            [$this->resolveLookupFieldName($lookupFieldName, $entityClass) => $lookupFieldValue]
        );
        if (null === $entity) {
            return null;
        }

        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    #[\Override]
    public function findEntity(string $entityClass, ?string $lookupFieldName, mixed $lookupFieldValue): ?object
    {
        if (ExtendHelper::isOutdatedEnumOptionEntity($entityClass)) {
            return $this->findEnumEntity($entityClass, $lookupFieldName, $lookupFieldValue);
        }

        return $this->entityLoader->loadEntity(
            $entityClass,
            [$this->resolveLookupFieldName($lookupFieldName, $entityClass) => $lookupFieldValue]
        );
    }

    private function getEnumLookupFieldValue(object $entity, ?string $lookupFieldName, string $entityClass): mixed
    {
        if (self::NAME === $lookupFieldName) {
            $enumOptionId = $this->getFieldValue($entity, self::INTERNAL_ID);
            $enumOptions = $this->enumOptionsProvider->getEnumInternalChoices(
                ExtendHelper::getEnumCode($entityClass)
            );

            return $enumOptions[$enumOptionId] ?? null;
        }

        return $this->getFieldValue($entity, $this->resolveEnumLookupFieldName($lookupFieldName));
    }

    private function findEnumEntityId(string $entityClass, ?string $lookupFieldName, mixed $lookupFieldValue): ?string
    {
        $enumCode = ExtendHelper::getEnumCode($entityClass);
        if (self::NAME === $lookupFieldName) {
            return $this->findEnumEntityIdByLabel($enumCode, $lookupFieldValue);
        }

        $lookupFieldName = $this->resolveEnumLookupFieldName($lookupFieldName);
        if (self::INTERNAL_ID === $lookupFieldName) {
            $enumOptions = $this->enumOptionsProvider->getEnumInternalChoices($enumCode);

            return isset($enumOptions[$lookupFieldValue])
                ? $lookupFieldValue
                : null;
        }

        return $this->entityLoader
            ->loadEntity(EnumOption::class, [self::ENUM_CODE => $enumCode, $lookupFieldName => $lookupFieldValue])
            ?->getInternalId();
    }

    private function findEnumEntity(string $entityClass, ?string $lookupFieldName, mixed $lookupFieldValue): ?EnumOption
    {
        $enumCode = ExtendHelper::getEnumCode($entityClass);
        if (self::NAME === $lookupFieldName) {
            $enumOptionId = $this->findEnumEntityIdByLabel($enumCode, $lookupFieldValue);
            if (null === $enumOptionId) {
                return null;
            }

            return $this->entityLoader->loadEntity(
                EnumOption::class,
                [self::ENUM_CODE => $enumCode, self::INTERNAL_ID => $enumOptionId]
            );
        }

        return $this->entityLoader->loadEntity(
            EnumOption::class,
            [self::ENUM_CODE => $enumCode, $this->resolveEnumLookupFieldName($lookupFieldName) => $lookupFieldValue]
        );
    }

    private function findEnumEntityIdByLabel(string $enumCode, string $label): ?string
    {
        $enumOptionId = null;
        $enumOptionLabel = mb_strtolower($label);
        $enumOptions = $this->enumOptionsProvider->getEnumInternalChoices($enumCode);
        foreach ($enumOptions as $itemId => $itemLabel) {
            if (mb_strtolower($itemLabel) === $enumOptionLabel) {
                $enumOptionId = $itemId;
                break;
            }
        }

        return $enumOptionId;
    }

    private function resolveLookupFieldName(?string $lookupFieldName, ?string $entityClass): string
    {
        if ($lookupFieldName) {
            return $lookupFieldName;
        }

        return $entityClass
            ? $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass)
            : self::ID;
    }

    private function resolveEnumLookupFieldName(?string $lookupFieldName): string
    {
        return !$lookupFieldName || self::ID === $lookupFieldName
            ? self::INTERNAL_ID
            : $lookupFieldName;
    }
}
