<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\FieldsChanges;

class FieldsChangesManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $className;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string         $className
     */
    public function __construct(DoctrineHelper $doctrineHelper, $className)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->className      = $className;
    }

    /**
     * @param object $entity
     * @param bool   $doRemove
     *
     * @return array
     */
    public function getChanges($entity, $doRemove = false)
    {
        $fieldChanges = $this->getOrCreateFieldsChanges($entity);

        $changedFields = $fieldChanges->getChangedFields();

        if ($doRemove) {
            $this->removeChanges($entity);
        }

        return $changedFields;
    }

    /**
     * @param object $entity
     * @param array  $changedFields
     *
     * @return FieldsChanges
     */
    public function setChanges($entity, array $changedFields)
    {
        $fieldChanges = $this->getOrCreateFieldsChanges($entity);

        return $fieldChanges->setChangedFields($changedFields);
    }

    /**
     * @param object $entity
     */
    public function removeChanges($entity)
    {
        $fieldChanges = $this->getOrCreateFieldsChanges($entity, false);

        $this->doctrineHelper
            ->getEntityManager($fieldChanges)
            ->remove($fieldChanges);
    }

    /**
     * @param object $entity
     * @param bool   $allowCreate
     *
     * @return FieldsChanges
     */
    protected function getOrCreateFieldsChanges($entity, $allowCreate = true)
    {
        $className  = $this->doctrineHelper->getEntityClass($entity);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        $em         = $this->doctrineHelper->getEntityManager($this->className);

        $fieldChanges = $em
            ->getRepository($this->className)
            ->findOneBy(
                [
                    'entityClass' => $className,
                    'entityId'    => $identifier
                ]
            );

        if ($fieldChanges || (!$fieldChanges && !$allowCreate)) {
            return $fieldChanges;
        }

        $fieldChanges = $this->doctrineHelper
            ->createEntityInstance($this->className)
            ->setEntityClass($className)
            ->setEntityId($identifier);

        $em->persist($fieldChanges);

        return $fieldChanges;
    }
}
