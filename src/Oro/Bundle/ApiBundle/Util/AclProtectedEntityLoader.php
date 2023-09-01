<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Provides a functionality to load an entity from the database taking into account ACL rules.
 */
class AclProtectedEntityLoader
{
    private DoctrineHelper $doctrineHelper;
    private EntityIdHelper $entityIdHelper;
    private QueryAclHelper $queryAclHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityIdHelper $entityIdHelper,
        QueryAclHelper $queryAclHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
        $this->queryAclHelper = $queryAclHelper;
    }

    /**
     * Loads an entity by its identifier.
     *
     * @param string                    $entityClass The class name of an entity
     * @param mixed                     $entityId    The identifier of an entity, can be a scalar or an array with
     *                                               the following schema: [identifier field name => value, ...]
     * @param EntityDefinitionConfig    $config      The configuration that is used to protect data by ACL
     * @param EntityIdMetadataInterface $metadata    The metadata that is used to adapt the given entity identifier
     *                                               to a criteria passed to "find" method of an entity manager
     * @param RequestType               $requestType The request type, for example "rest", "soap", etc.
     *
     * @return object|null
     *
     * @throws AccessDeniedException when an access to the requested entity is denied
     * @throws NonUniqueResultException when more than one entity was found
     */
    public function findEntity(
        string $entityClass,
        mixed $entityId,
        EntityDefinitionConfig $config,
        EntityIdMetadataInterface $metadata,
        RequestType $requestType
    ): ?object {
        // try to load an entity by ACL protected query
        $qb = $this->getFindEntityQueryBuilder($entityClass, $entityId, $metadata);
        $entity = $this->queryAclHelper->protectQuery($qb, $config, $requestType)->getOneOrNullResult();
        if (null === $entity) {
            // use a query without ACL protection to check if an entity exists in DB
            $qb = $this->getFindEntityQueryBuilder($entityClass, $entityId, $metadata);
            $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
            if (\count($idFieldNames) !== 0) {
                $qb->select('e.' . reset($idFieldNames));
            }
            $notAclProtectedData = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
            if ($notAclProtectedData) {
                throw new AccessDeniedException('No access to the entity.');
            }
        }

        return $entity;
    }

    /**
     * Loads an entity by the given criteria.
     *
     * @param string                    $entityClass The class name of an entity
     * @param array                     $criteria    [field name => value, ...]
     * @param EntityDefinitionConfig    $config      The configuration that is used to protect data by ACL
     * @param EntityIdMetadataInterface $metadata    The metadata that is used to adapt the given criteria
     * @param RequestType               $requestType The request type, for example "rest", "soap", etc.
     *
     * @return object|null
     *
     * @throws \InvalidArgumentException when the given criteria contains unknown fields
     * @throws AccessDeniedException when an access to the requested entity is denied
     * @throws NonUniqueResultException when more than one entity was found
     */
    public function findEntityBy(
        string $entityClass,
        array $criteria,
        EntityDefinitionConfig $config,
        EntityIdMetadataInterface $metadata,
        RequestType $requestType
    ): ?object {
        $criteria = $this->buildFindByCriteria($criteria, $metadata);
        // try to load an entity by ACL protected query
        $qb = $this->getFindEntityByQueryBuilder($entityClass, $criteria);
        $entity = $this->queryAclHelper->protectQuery($qb, $config, $requestType)->getOneOrNullResult();
        if (null === $entity) {
            // use a query without ACL protection to check if an entity exists in DB
            $qb = $this->getFindEntityByQueryBuilder($entityClass, $criteria);
            $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
            if (\count($idFieldNames) !== 0) {
                $qb->select('e.' . reset($idFieldNames));
            }
            $notAclProtectedData = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
            if ($notAclProtectedData) {
                throw new AccessDeniedException('No access to the entity.');
            }
        }

        return $entity;
    }

    private function getFindEntityQueryBuilder(
        string $entityClass,
        mixed $entityId,
        EntityIdMetadataInterface $metadata
    ): QueryBuilder {
        $qb = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $metadata);

        return $qb;
    }

    private function getFindEntityByQueryBuilder(string $entityClass, array $criteria): QueryBuilder
    {
        $qb = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        foreach ($criteria as $fieldName => $fieldValue) {
            $qb->andWhere(QueryBuilderUtil::sprintf('e.%1$s = :%1$s', $fieldName));
            $qb->setParameter($fieldName, $fieldValue);
        }

        return $qb;
    }

    /**
     * @throws \InvalidArgumentException when the given criteria contains unknown fields
     */
    private function buildFindByCriteria(array $criteria, EntityIdMetadataInterface $metadata): array
    {
        $result = [];
        foreach ($criteria as $fieldName => $fieldValue) {
            $propertyName = $metadata->getPropertyPath($fieldName);
            if (null === $propertyName) {
                throw new \InvalidArgumentException(sprintf(
                    'The entity "%s" does not have metadata for the "%s" property.',
                    $metadata->getClassName(),
                    $fieldName
                ));
            }
            $result[$propertyName] = $fieldValue;
        }

        return $result;
    }
}
