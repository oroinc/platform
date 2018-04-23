<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Finds detached properties in entity and reloads them from UnitOfWork.
 *
 * After EntityManager::clear method called some entities could be in detached state,
 * for example User in SecurityContext.
 */
class EntityDetachFixer
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param FieldHelper $fieldHelper
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        FieldHelper $fieldHelper,
        PropertyAccessor $propertyAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->fieldHelper = $fieldHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Finds detached properties in entity and reloads them from UnitOfWork
     *
     * @param object $entity doctrine entity
     * @param int $level maximum nesting level
     */
    public function fixEntityAssociationFields($entity, $level = 0)
    {
        if ($level < 0) {
            return;
        }

        // we should use entityFieldProvider through fieldHelper
        // to get relations data and avoid deleted relations in result list
        $relations = $this->fieldHelper->getRelations(ClassUtils::getClass($entity));
        if (!$relations) {
            return;
        }

        foreach ($relations as $associationMapping) {
            $fieldName = $associationMapping['name'];
            if (!$this->propertyAccessor->isReadable($entity, $fieldName)) {
                continue;
            }

            $value = $this->propertyAccessor->getValue($entity, $fieldName);

            if ($value && is_object($value)) {
                if ($value instanceof Collection) {
                    $this->fixCollectionField($value, $level);
                } else {
                    $this->fixEntityField($entity, $fieldName, $value, $level);
                }
            }
        }
    }

    /**
     * @param Collection $collection
     * @param $level
     */
    protected function fixCollectionField($collection, $level)
    {
        foreach ($collection as $key => $value) {
            if ($this->isEntityDetached($value)) {
                $value = $this->reloadEntity($value);
                $collection->set($key, $value);
            } else {
                $this->fixEntityAssociationFields($value, $level - 1);
            }
        }
    }

    /**
     * @param mixed $entity
     * @param string $fieldName
     * @param mixed $value
     * @param int $level
     */
    protected function fixEntityField($entity, $fieldName, $value, $level)
    {
        if ($this->isEntityDetached($value)) {
            $value = $this->reloadEntity($value);
            $this->propertyAccessor->setValue($entity, $fieldName, $value);
        } else {
            $this->fixEntityAssociationFields($value, $level - 1);
        }
    }

    /**
     * @param object $entity
     * @return object
     */
    protected function reloadEntity($entity)
    {
        $entityClass = ClassUtils::getClass($entity);

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrineHelper->getEntityManager($entityClass);
        $id = $entityManager->getClassMetadata($entityClass)->getIdentifierValues($entity);

        return $entityManager->getReference($entityClass, $id);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isEntityDetached($entity)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($entity);

        return $entityManager->getUnitOfWork()->getEntityState($entity) === UnitOfWork::STATE_DETACHED;
    }
}
