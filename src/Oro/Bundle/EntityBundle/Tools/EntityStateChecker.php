<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tools;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Checks if an entity is new or changed.
 */
class EntityStateChecker
{
    private DoctrineHelper $doctrineHelper;

    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(DoctrineHelper $doctrineHelper, PropertyAccessorInterface $propertyAccessor)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $entity Entity to check if it is new or already persisted.
     *
     * @return bool
     */
    public function isNewEntity(object $entity): bool
    {
        return $this->doctrineHelper->isNewEntity($entity);
    }

    /**
     * @param object $entity Entity to check for changes
     * @param array<string> $fieldNamesToCheck Entity field names to check for changes
     *
     * @return bool
     */
    public function isChangedEntity(object $entity, array $fieldNamesToCheck): bool
    {
        if (!$fieldNamesToCheck) {
            throw new \InvalidArgumentException('Argument $fieldNamesToCheck was not expected to be empty');
        }

        $unitOfWork = $this->doctrineHelper->getEntityManager($entity)->getUnitOfWork();
        $originalData = $unitOfWork->getOriginalEntityData($entity);

        foreach ($fieldNamesToCheck as $fieldName) {
            if (!array_key_exists($fieldName, $originalData)) {
                return true;
            }

            if ($this->propertyAccessor->getValue($entity, $fieldName) !== $originalData[$fieldName]) {
                return true;
            }
        }

        return false;
    }

    public function getOriginalEntityFieldData(object $entity, string $fieldName): mixed
    {
        $unitOfWork = $this->doctrineHelper->getEntityManager($entity)->getUnitOfWork();

        return $unitOfWork->getOriginalEntityData($entity)[$fieldName] ?? null;
    }
}
