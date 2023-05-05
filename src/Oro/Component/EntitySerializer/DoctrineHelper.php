<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides a set of helper methods to work with manageable ORM entities.
 */
class DoctrineHelper
{
    private const KEY_METADATA = 'metadata';
    private const KEY_ID_FIELD = 'id';

    private ManagerRegistry $doctrine;
    private array $cache = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Checks whether the given class represents a manageable entity.
     */
    public function isManageableEntity(string $entityClass): bool
    {
        return null !== $this->doctrine->getManagerForClass($entityClass);
    }

    /**
     * Gets the entity manager associated with the given entity class.
     */
    public function getEntityManager(string $entityClass): EntityManagerInterface
    {
        /** @var EntityManagerInterface|null $em */
        $em = $this->doctrine->getManagerForClass($entityClass);
        if (null === $em) {
            throw new \RuntimeException(sprintf('Entity class "%s" is not manageable.', $entityClass));
        }

        return $em;
    }

    /**
     * Creates a new query builder object for the given entity class.
     */
    public function createQueryBuilder(string $entityClass, string $alias): QueryBuilder
    {
        return $this->getEntityManager($entityClass)
            ->getRepository($entityClass)
            ->createQueryBuilder($alias);
    }

    /**
     * Gets the ORM metadata for the given entity class.
     */
    public function getEntityMetadata(string $entityClass): EntityMetadata
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
     * Gets the full class name for the given entity.
     *
     * @param string $entityName The name of the entity. Can be bundle:entity or full class name
     *
     * @return string The full class name
     *
     * @throws \InvalidArgumentException if the given entity name cannot be resolved
     */
    public function resolveEntityClass(string $entityName): string
    {
        $parts = explode(':', $entityName);
        $numberOfParts = \count($parts);
        if ($numberOfParts <= 1) {
            // the given entity name is not in bundle:entity format; it is supposed that it is the full class name
            return $entityName;
        }
        if ($numberOfParts > 2) {
            throw new \InvalidArgumentException(sprintf(
                'Incorrect entity name: %s. Expected the full class name or bundle:entity.',
                $entityName
            ));
        }

        return $this->doctrine->getAliasNamespace($parts[0]) . '\\' . $parts[1];
    }

    /**
     * Gets the root alias of the given query.
     *
     * @throws QueryException if the given query builder does not have a root alias or has more than one root aliases
     */
    public function getRootAlias(QueryBuilder $qb): string
    {
        return QueryBuilderUtil::getSingleRootAlias($qb);
    }

    /**
     * Gets the root entity class of the given query.
     *
     * @throws QueryException if the given query builder does not have a root entity or has more than one root entities
     */
    public function getRootEntityClass(QueryBuilder $qb): string
    {
        return $this->resolveEntityClass(QueryBuilderUtil::getSingleRootEntity($qb));
    }

    /**
     * Gets the name of entity identifier field if an entity has a single-field identifier.
     */
    public function getEntityIdFieldName(string $entityClass): string
    {
        if (isset($this->cache[$entityClass][self::KEY_ID_FIELD])) {
            return $this->cache[$entityClass][self::KEY_ID_FIELD];
        }

        $idFieldName = $this->getEntityMetadata($entityClass)->getSingleIdentifierFieldName();
        $this->cache[$entityClass][self::KEY_ID_FIELD] = $idFieldName;

        return $idFieldName;
    }

    /**
     * Gets the data-type of entity identifier field if an entity has a single-field identifier.
     */
    public function getEntityIdType(string $entityClass): ?string
    {
        return $this->getEntityMetadata($entityClass)
            ->getFieldType($this->getEntityIdFieldName($entityClass));
    }

    /**
     * Gets the target class name of an association by the given property path.
     *
     * @param EntityMetadata $entityMetadata
     * @param string[]       $propertyPath
     *
     * @return string|null
     */
    public function getAssociationTargetClass(EntityMetadata $entityMetadata, array $propertyPath): ?string
    {
        $targetClass = null;
        $currentMetadata = $entityMetadata;
        foreach ($propertyPath as $property) {
            if (null === $currentMetadata) {
                $currentMetadata = $this->getEntityMetadata($targetClass);
            }
            if (!$currentMetadata->isAssociation($property)) {
                $targetClass = null;
                break;
            }
            $targetClass = $currentMetadata->getAssociationTargetClass($property);
            $currentMetadata = null;
        }

        return $targetClass;
    }
}
