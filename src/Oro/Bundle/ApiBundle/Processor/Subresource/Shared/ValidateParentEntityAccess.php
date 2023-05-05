<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Checks whether an VIEW access to the parent entity is granted.
 */
class ValidateParentEntityAccess implements ProcessorInterface
{
    public const OPERATION_NAME = 'validate_parent_entity_access';

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
        /** @var SubresourceContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the access validation was already performed
            return;
        }

        $parentConfig = $context->getParentConfig();
        if (null === $parentConfig) {
            // unsupported API resource
            return;
        }

        if (null === $parentConfig->getField($context->getAssociationName())) {
            // skip sub-resources that do not associated with any field in the parent entity config
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

        $this->checkParentEntityAccess(
            $parentEntityClass,
            $context->getParentId(),
            $parentConfig,
            $parentMetadata,
            $context->getRequestType()
        );

        $context->setProcessed(self::OPERATION_NAME);
    }

    private function checkParentEntityAccess(
        string $parentEntityClass,
        mixed $parentEntityId,
        EntityDefinitionConfig $parentConfig,
        EntityIdMetadataInterface $parentMetadata,
        RequestType $requestType
    ): void {
        // try to get an entity by ACL protected query
        $data = $this->queryAclHelper
            ->protectQuery(
                $this->getQueryBuilder($parentEntityClass, $parentEntityId, $parentMetadata),
                $parentConfig,
                $requestType
            )
            ->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$data) {
            // use a query without ACL protection to check if an entity exists in DB
            $data = $this->getQueryBuilder($parentEntityClass, $parentEntityId, $parentMetadata)
                ->getQuery()
                ->getOneOrNullResult(Query::HYDRATE_ARRAY);
            if ($data) {
                throw new AccessDeniedException('No access to the parent entity.');
            }
            throw new NotFoundHttpException('The parent entity does not exist.');
        }
    }

    private function getQueryBuilder(
        string $parentEntityClass,
        mixed $parentEntityId,
        EntityIdMetadataInterface $parentMetadata
    ): QueryBuilder {
        $qb = $this->doctrineHelper->createQueryBuilder($parentEntityClass, 'e');
        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($parentEntityClass);
        if (\count($idFieldNames) !== 0) {
            $qb->select('e.' . reset($idFieldNames));
        }
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $qb,
            $parentEntityId,
            $parentMetadata
        );

        return $qb;
    }
}
