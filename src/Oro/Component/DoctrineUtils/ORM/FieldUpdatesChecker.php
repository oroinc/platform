<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FieldUpdatesChecker
{
    use ChangedEntityGeneratorTrait;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry             $managerRegistry
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(ManagerRegistry $managerRegistry, PropertyAccessorInterface $propertyAccessor)
    {
        $this->managerRegistry = $managerRegistry;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return bool
     */
    public function isRelationFieldChanged($entity, $fieldName)
    {
        $field = $this->propertyAccessor->getValue($entity, $fieldName);

        if ($field instanceof Collection) {
            foreach ($field as $fieldElement) {
                if ($this->inChangedEntities($fieldElement)) {
                    return true;
                }
            }
        } elseif ($this->inChangedEntities($field)) {
            return true;
        }

        return false;
    }

    /**
     * @return UnitOfWork
     */
    private function getUnitOfWork()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManager();

        return $entityManager->getUnitOfWork();
    }

    /**
     * @param object $entity
     * @return bool
     */
    private function inChangedEntities($entity)
    {
        foreach ($this->getChangedEntities($this->getUnitOfWork()) as $changedEntity) {
            if (spl_object_hash($changedEntity) === spl_object_hash($entity)) {
                return true;
            }
        }

        return false;
    }
}
