<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class DoctrineHelper
{
    const KEY_METADATA = 'metadata';
    const KEY_ID_FIELD = 'id';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $cache = [];

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Checks whether the given class is manageable entity.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function isManageableEntity($entityClass)
    {
        return null !== $this->doctrine->getManagerForClass($entityClass);
    }

    /**
     * @param string $entityClass
     *
     * @return EntityManager
     *
     * @throws \RuntimeException
     */
    public function getEntityManager($entityClass)
    {
        $em = $this->doctrine->getManagerForClass($entityClass);
        if (!$em) {
            throw new \RuntimeException(sprintf('Entity class "%s" is not manageable.', $entityClass));
        }

        return $em;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityRepository
     */
    public function getEntityRepository($entityClass)
    {
        return $this->getEntityManager($entityClass)->getRepository($entityClass);
    }

    /**
     * @param string $entityClass
     *
     * @return EntityMetadata
     */
    public function getEntityMetadata($entityClass)
    {
        if (isset($this->cache[$entityClass][self::KEY_METADATA])) {
            return $this->cache[$entityClass][self::KEY_METADATA];
        }

        $metadata = new EntityMetadata(
            $this->getEntityManager($entityClass)->getClassMetadata($entityClass)
        );
        $this->cache[$entityClass][self::KEY_METADATA] = $metadata;

        return $metadata;
    }

    /**
     * Gets the full class name for the given entity
     *
     * @param string $entityName The name of the entity. Can be bundle:entity or full class name
     *
     * @return string The full class name
     * @throws \InvalidArgumentException
     */
    public function resolveEntityClass($entityName)
    {
        $split = explode(':', $entityName);
        if (count($split) <= 1) {
            // The given entity name is not in bundle:entity format. Suppose that it is the full class name
            return $entityName;
        } elseif (count($split) > 2) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Incorrect entity name: %s. Expected the full class name or bundle:entity.',
                    $entityName
                )
            );
        }

        return $this->doctrine->getAliasNamespace($split[0]) . '\\' . $split[1];
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getRootAlias(QueryBuilder $qb)
    {
        $aliases = $qb->getRootAliases();
        if (count($aliases) !== 1) {
            if (count($aliases) === 0) {
                throw new \RuntimeException(
                    'Cannot get root alias. A query builder has no root entity.'
                );
            } else {
                throw new \RuntimeException(
                    'Cannot get root alias. A query builder has more than one root entity.'
                );
            }
        }

        return $aliases[0];
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getRootEntityClass(QueryBuilder $qb)
    {
        $entities = $qb->getRootEntities();
        if (count($entities) !== 1) {
            if (count($entities) === 0) {
                throw new \RuntimeException(
                    'Cannot get root entity class. A query builder has no root entity.'
                );
            } else {
                throw new \RuntimeException(
                    'Cannot get root entity class. A query builder has more than one root entity.'
                );
            }
        }

        return $this->resolveEntityClass($entities[0]);
    }

    /**
     * Gets the name of entity identifier field if an entity has a single-field identifier
     *
     * @param string $entityClass
     *
     * @return string
     */
    public function getEntityIdFieldName($entityClass)
    {
        if (isset($this->cache[$entityClass][self::KEY_ID_FIELD])) {
            return $this->cache[$entityClass][self::KEY_ID_FIELD];
        }

        $idFieldName = $this->getEntityMetadata($entityClass)->getSingleIdentifierFieldName();
        $this->cache[$entityClass][self::KEY_ID_FIELD] = $idFieldName;

        return $idFieldName;
    }
}
