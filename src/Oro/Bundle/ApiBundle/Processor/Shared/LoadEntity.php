<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\QueryFactory;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads the entity from the database and adds it to the context.
 */
class LoadEntity implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityIdHelper */
    private $entityIdHelper;

    /** @var QueryFactory */
    private $queryFactory;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityIdHelper $entityIdHelper
     * @param QueryFactory   $queryFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityIdHelper $entityIdHelper,
        QueryFactory $queryFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // the entity is already loaded
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // unsupported API resource
            return;
        }

        $entityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $config
        );
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            // unsupported API resource
            return;
        }

        $entity = $this->loadEntity(
            $entityClass,
            $context->getId(),
            $config,
            $metadata
        );
        if (null !== $entity) {
            $context->setResult($entity);
        }
    }

    /**
     * @param string                    $entityClass
     * @param mixed                     $entityId
     * @param EntityDefinitionConfig    $config
     * @param EntityIdMetadataInterface $metadata
     *
     * @return object|null
     */
    private function loadEntity(
        string $entityClass,
        $entityId,
        EntityDefinitionConfig $config,
        EntityIdMetadataInterface $metadata
    ) {
        // try to load an entity by ACL protected query
        $entity = $this->queryFactory
            ->getQuery($this->getQueryBuilder($entityClass, $entityId, $metadata), $config)
            ->getOneOrNullResult();
        if (null === $entity) {
            // use a query without ACL protection to check if an entity exists in DB
            $qb = $this->getQueryBuilder($entityClass, $entityId, $metadata);
            $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
            if (\count($idFieldNames) !== 0) {
                $qb->select('e.' . \reset($idFieldNames));
            }
            $notAclProtectedData = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
            if ($notAclProtectedData) {
                throw new AccessDeniedException('No access to the entity.');
            }
        }

        return $entity;
    }

    /**
     * @param string                    $entityClass
     * @param mixed                     $entityId
     * @param EntityIdMetadataInterface $metadata
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder(
        string $entityClass,
        $entityId,
        EntityIdMetadataInterface $metadata
    ): QueryBuilder {
        $qb = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $qb,
            $entityId,
            $metadata
        );

        return $qb;
    }
}
