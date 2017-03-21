<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Component\PropertyAccess\PropertyAccessor;

class FieldUpdatesChecker
{
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
        $updatedEntities = $this->getUnitOfWork()->getScheduledEntityUpdates();
        $field = $this->propertyAccessor->getValue($entity, $fieldName);

        if ($field instanceof Collection) {
            foreach ($field as $fieldElement) {
                if (in_array($fieldElement, $updatedEntities, true)) {
                    return true;
                }
            }
        } elseif (in_array($field, $updatedEntities, true)) {
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
}
