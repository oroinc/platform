<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\Exception;

class DoctrineHelper
{
    /** @var ManagerRegistry */
    protected $registry;

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
            // @todo This code doesn't support composite keys.
            return ['id' => $entity->getId()];
        }

        return $this
            ->getEntityMetadata($entity)
            ->getIdentifierValues($entity);
    }

    /**
     * Check entity is new
     *
     * @param object $entity
     * @return bool
     */
    public function isNewEntity($entity)
    {
        $identifierValues = $this->getEntityMetadata($entity)->getIdentifierValues($entity);

        return count($identifierValues) === 0;
    }

    /**
     * Gets the root entity alias of the query.
     *
     * @param QueryBuilder $qb
     * @param bool         $triggerException
     *
     * @return string|null
     *
     * @throws Exception\InvalidEntityException
     */
    public function getSingleRootAlias(QueryBuilder $qb, $triggerException = true)
    {
        $rootAliases = $qb->getRootAliases();

        $result = null;
        if (count($rootAliases) !== 1) {
            if ($triggerException) {
                $errorReason = count($rootAliases) === 0
                    ? 'the query has no any root aliases'
                    : sprintf('the query has several root aliases. "%s"', implode(', ', $rootAliases));

                throw new Exception\InvalidEntityException(
                    sprintf(
                        'Can\'t get single root alias for the given query. Reason: %s.',
                        $errorReason
                    )
                );
            }
        } else {
            $result = $rootAliases[0];
        }

        return $result;
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
     * @param object|string $entityOrClass
     * @return bool
     */
    public function isManageableEntity($entityOrClass)
    {
        $entityClass = $this->getEntityClass($entityOrClass);

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
     * Gets the EntityManager associated with the given class.
     *
     * @param string $entityClass    The real class name of an entity
     * @param bool   $throwException Whether to throw exception in case the entity is not manageable
     *
     * @return EntityManager|null
     *
     * @throws Exception\NotManageableEntityException if the EntityManager was not found and $throwException is TRUE
     */
    public function getEntityManagerForClass($entityClass, $throwException = true)
    {
        $manager = $this->registry->getManagerForClass($entityClass);
        if (null === $manager && $throwException) {
            throw new Exception\NotManageableEntityException($entityClass);
        }

        return $manager;
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
     * Gets the repository for the given entity class.
     *
     * @param string $entityClass The real class name of an entity
     *
     * @return EntityRepository
     */
    public function getEntityRepositoryForClass($entityClass)
    {
        return $this
            ->getEntityManagerForClass($entityClass)
            ->getRepository($entityClass);
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

    /**
     * Calculates the page offset
     *
     * @param int $page  The page number
     * @param int $limit The maximum number of items per page
     *
     * @return int
     */
    public function getPageOffset($page, $limit)
    {
        return $page > 0
            ? ($page - 1) * $limit
            : 0;
    }

    /**
     * Applies the given joins for the query builder
     *
     * @param QueryBuilder $qb
     * @param array|null   $joins
     */
    public function applyJoins(QueryBuilder $qb, $joins)
    {
        if (empty($joins)) {
            return;
        }

        $qb->distinct(true);
        $rootAlias = $this->getSingleRootAlias($qb);
        foreach ($joins as $key => $val) {
            if (empty($val)) {
                $qb->leftJoin($rootAlias . '.' . $key, $key);
            } elseif (is_array($val)) {
                if (isset($val['join'])) {
                    $join = $val['join'];
                    if (false === strpos($join, '.')) {
                        $join = $rootAlias . '.' . $join;
                    }
                } else {
                    $join = $rootAlias . '.' . $key;
                }
                $condition     = null;
                $conditionType = null;
                if (isset($val['condition'])) {
                    $condition     = $val['condition'];
                    $conditionType = Join::WITH;
                }
                if (isset($val['conditionType'])) {
                    $conditionType = $val['conditionType'];
                }
                $qb->leftJoin($join, $key, $conditionType, $condition);
            } else {
                $qb->leftJoin($rootAlias . '.' . $val, $val);
            }
        }
    }

    /**
     * Checks the given criteria and converts them to Criteria object if needed
     *
     * @param Criteria|array|null $criteria
     *
     * @return Criteria
     */
    public function normalizeCriteria($criteria)
    {
        if (null === $criteria) {
            $criteria = new Criteria();
        } elseif (is_array($criteria)) {
            $newCriteria = new Criteria();
            foreach ($criteria as $fieldName => $value) {
                $newCriteria->andWhere(Criteria::expr()->eq($fieldName, $value));
            }

            $criteria = $newCriteria;
        }

        return $criteria;
    }
}
