<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads the parent entity from the database and adds it to the context.
 */
class LoadParentEntity implements ProcessorInterface
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
        /** @var ChangeRelationshipContext $context */

        if ($context->hasParentEntity()) {
            // the parent entity is already loaded
            return;
        }

        $parentConfig = $context->getParentConfig();
        if (null === $parentConfig) {
            // unsupported API resource
            return;
        }

        $parentEntityClass = $context->getManageableParentEntityClass($this->doctrineHelper);
        if (!$parentEntityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $parentMetadata = $context->getParentMetadata();
        if (null === $parentMetadata) {
            // unsupported API resource
            return;
        }

        $parentEntity = $this->loadParentEntity(
            $parentEntityClass,
            $context->getParentId(),
            $parentConfig,
            $parentMetadata,
            $context->getRequestType()
        );
        if (null !== $parentEntity) {
            $context->setParentEntity($parentEntity);
        }
    }

    private function loadParentEntity(
        string $parentEntityClass,
        mixed $parentEntityId,
        EntityDefinitionConfig $parentConfig,
        EntityIdMetadataInterface $parentMetadata,
        RequestType $requestType
    ): ?object {
        // try to load an entity by ACL protected query
        $parentEntity = $this->queryAclHelper
            ->protectQuery(
                $this->getQueryBuilder($parentEntityClass, $parentEntityId, $parentMetadata),
                $parentConfig,
                $requestType
            )
            ->getOneOrNullResult();
        if (null === $parentEntity) {
            // use a query without ACL protection to check if an entity exists in DB
            $qb = $this->getQueryBuilder($parentEntityClass, $parentEntityId, $parentMetadata);
            $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($parentEntityClass);
            if (\count($idFieldNames) !== 0) {
                $qb->select('e.' . reset($idFieldNames));
            }
            $notAclProtectedData = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
            if ($notAclProtectedData) {
                throw new AccessDeniedException('No access to the parent entity.');
            }
        }

        return $parentEntity;
    }

    private function getQueryBuilder(
        string $parentEntityClass,
        mixed $parentEntityId,
        EntityIdMetadataInterface $parentMetadata
    ): QueryBuilder {
        $qb = $this->doctrineHelper->createQueryBuilder($parentEntityClass, 'e');
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $qb,
            $parentEntityId,
            $parentMetadata
        );

        return $qb;
    }
}
