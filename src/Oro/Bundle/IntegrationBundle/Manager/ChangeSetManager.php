<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\ChangeSet;

class ChangeSetManager
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $changeSetClassName;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string         $changeSetClassName
     */
    public function __construct(DoctrineHelper $doctrineHelper, $changeSetClassName)
    {
        $this->doctrineHelper     = $doctrineHelper;
        $this->changeSetClassName = $changeSetClassName;
    }

    /**
     * @param object $entity
     * @param string $type
     *
     * @return array|null
     */
    public function getChanges($entity, $type = ChangeSet::TYPE_LOCAL)
    {
        $this->ensureType($type);

        $changeSet = $this->getChangeSetForEntity($entity);

        return $this->getPropertyAccessor()->getValue($changeSet, $type);
    }

    /**
     * @param object $entity
     * @param string $type
     * @param array  $changes
     *
     * @return ChangeSet
     */
    public function setChanges($entity, $type = ChangeSet::TYPE_LOCAL, array $changes)
    {
        $this->ensureType($type);

        $changeSet = $this->getChangeSetForEntity($entity);

        $this->getPropertyAccessor()->setValue($changeSet, $type, $changes);

        return $changeSet;
    }

    /**
     * @param object $entity
     * @param string $type
     *
     * @return ChangeSet|null
     */
    public function removeChanges($entity, $type = ChangeSet::TYPE_LOCAL)
    {
        $this->ensureType($type);

        $changeSet = $this->getChangeSetForEntity($entity, false);

        $removeEntity = true;
        foreach (ChangeSet::$types as $changeSetType) {
            if ($changeSetType === $type) {
                continue;
            }

            if ($this->getPropertyAccessor()->getValue($changeSet, $changeSetType)) {
                $removeEntity = false;
            }
        }

        if (!$removeEntity) {
            $this->getPropertyAccessor()->setValue($changeSet, $type, null);

            return $changeSet;
        }

        $this->doctrineHelper
            ->getEntityManager($changeSet)
            ->remove($changeSet);

        return null;
    }

    /**
     * @param $type
     */
    protected function ensureType($type)
    {
        if (!in_array($type, ChangeSet::$types)) {
            throw new \InvalidArgumentException(
                sprintf('Expected one of "%s", "%s" given', implode(',', ChangeSet::$types), $type)
            );
        }
    }

    /**
     * @param object $entity
     * @param bool   $allowCreate
     *
     * @return ChangeSet
     */
    protected function getChangeSetForEntity($entity, $allowCreate = true)
    {
        $className  = $this->doctrineHelper->getEntityClass($entity);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $em = $this->doctrineHelper->getEntityManager($this->changeSetClassName);

        $changeSet = $em
            ->getRepository($this->changeSetClassName)
            ->findOneBy(
                [
                    'entityClass' => $className,
                    'entityId'    => $identifier
                ]
            );

        if (!$changeSet && $allowCreate) {
            $changeSet = $this->doctrineHelper
                ->createEntityInstance($this->changeSetClassName)
                ->setEntityClass($className)
                ->setEntityId($identifier);

            $em->persist($changeSet);
        }

        if (!$changeSet) {
            throw new \InvalidArgumentException(
                sprintf('Entity with id %s not exists', $identifier)
            );
        }

        return $changeSet;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
