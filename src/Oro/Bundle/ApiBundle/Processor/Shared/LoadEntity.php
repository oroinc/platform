<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads the entity from the database and adds it to the context.
 */
class LoadEntity implements ProcessorInterface
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
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

        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
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
            $metadata,
            $context->getRequestType()
        );
        if (null !== $entity) {
            $context->setResult($entity);
        }
    }

    private function loadEntity(
        string $entityClass,
        mixed $entityId,
        EntityDefinitionConfig $config,
        EntityIdMetadataInterface $metadata,
        RequestType $requestType
    ): ?object {
        // try to load an entity by ACL protected query
        $entity = $this->queryAclHelper
            ->protectQuery(
                $this->getQueryBuilder($entityClass, $entityId, $metadata),
                $config,
                $requestType
            )
            ->getOneOrNullResult();
        if (null === $entity) {
            // use a query without ACL protection to check if an entity exists in DB
            $qb = $this->getQueryBuilder($entityClass, $entityId, $metadata);
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

    private function getQueryBuilder(
        string $entityClass,
        mixed $entityId,
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
