<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Exception;

class DoctrineHelper
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object|string $entityOrClass
     * @return string
     */
    public function getEntityClass($entityOrClass)
    {
        return is_object($entityOrClass)
            ? ClassUtils::getClass($entityOrClass)
            : ClassUtils::getRealClass($entityOrClass);
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getEntityIdentifier($entity)
    {
        // check if we can use getId method to fast get the identifier
        if (method_exists($entity, 'getId')) {
            return ['id' => $entity->getId()];
        }

        return $this
            ->getEntityMetadata($entity)
            ->getIdentifierValues($entity);
    }

    /**
     * @param object $entity
     * @param bool   $triggerException
     * @return mixed|null
     * @throws Exception\InvalidEntityException
     */
    public function getSingleEntityIdentifier($entity, $triggerException = true)
    {
        $entityIdentifier = $this->getEntityIdentifier($entity);

        $result = null;
        if (count($entityIdentifier) > 1) {
            if ($triggerException) {
                throw new Exception\InvalidEntityException(
                    sprintf(
                        'Can\'t get single identifier for "%s" entity.',
                        $this->getEntityClass($entity)
                    )
                );
            }
        } else {
            $result = $entityIdentifier ? current($entityIdentifier) : null;
        }

        return $result;
    }

    /**
     * @param object|string $entityOrClass
     * @return string[]
     */
    public function getEntityIdentifierFieldNames($entityOrClass)
    {
        return $this
            ->getEntityMetadata($entityOrClass)
            ->getIdentifierFieldNames();
    }

    /**
     * @param object|string $entityOrClass
     * @param bool          $triggerException
     * @return string|null
     * @throws Exception\InvalidEntityException
     */
    public function getSingleEntityIdentifierFieldName($entityOrClass, $triggerException = true)
    {
        $fieldNames = $this->getEntityIdentifierFieldNames($entityOrClass);

        $result = null;
        if (count($fieldNames) > 1) {
            if ($triggerException) {
                throw new Exception\InvalidEntityException(
                    sprintf(
                        'Can\'t get single identifier field name for "%s" entity.',
                        $this->getEntityClass($entityOrClass)
                    )
                );
            }
        } else {
            $result = $fieldNames ? current($fieldNames) : null;
        }

        return $result;
    }

    /**
     * @param object|string $entityOrClass
     * @param bool          $triggerException
     * @return string|null
     * @throws Exception\InvalidEntityException
     */
    public function getSingleEntityIdentifierFieldType($entityOrClass, $triggerException = true)
    {
        $metadata   = $this->getEntityMetadata($entityOrClass);
        $fieldNames = $metadata->getIdentifierFieldNames();

        $result = null;
        if (count($fieldNames) !== 1) {
            if ($triggerException) {
                throw new Exception\InvalidEntityException(
                    sprintf(
                        'Can\'t get single identifier field type for "%s" entity.',
                        $this->getEntityClass($entityOrClass)
                    )
                );
            }
        } else {
            $result = $metadata->getTypeOfField(current($fieldNames));
        }

        return $result;
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function isManageableEntity($entity)
    {
        $entityClass = $this->getEntityClass($entity);

        return null !== $this->registry->getManagerForClass($entityClass);
    }

    /**
     * @param $entityOrClass
     * @return ClassMetadata
     * @throws Exception\NotManageableEntityException
     */
    public function getEntityMetadata($entityOrClass)
    {
        $entityClass   = $this->getEntityClass($entityOrClass);
        $entityManager = $this->registry->getManagerForClass($entityClass);
        if (!$entityManager) {
            throw new Exception\NotManageableEntityException($entityClass);
        }

        return $entityManager->getClassMetadata($entityClass);
    }

    /**
     * @param string|object $entityOrClass
     * @return EntityManager
     * @throws Exception\NotManageableEntityException
     */
    public function getEntityManager($entityOrClass)
    {
        $entityClass   = $this->getEntityClass($entityOrClass);
        $entityManager = $this->registry->getManagerForClass($entityClass);
        if (!$entityManager) {
            throw new Exception\NotManageableEntityException($entityClass);
        }

        return $entityManager;
    }

    /**
     * @param string|object $entityOrClass
     * @return EntityRepository
     */
    public function getEntityRepository($entityOrClass)
    {
        $entityClass   = $this->getEntityClass($entityOrClass);
        $entityManager = $this->getEntityManager($entityClass);

        return $entityManager->getRepository($entityClass);
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     * @return object
     */
    public function getEntityReference($entityClass, $entityId)
    {
        return $this
            ->getEntityManager($entityClass)
            ->getReference($entityClass, $entityId);
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     * @return object|null
     */
    public function getEntity($entityClass, $entityId)
    {
        return $this
            ->getEntityManager($entityClass)
            ->getRepository($entityClass)
            ->find($entityId);
    }

    /**
     * @param string $entityClass
     * @return object
     */
    public function createEntityInstance($entityClass)
    {
        return $this
            ->getEntityMetadata($entityClass)
            ->newInstance();
    }
}
