<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\AttachmentBundle\Api\MultiFileAssociationProvider;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get multi files and multi images associations.
 */
class BuildMultiFileSubresourceQuery implements ProcessorInterface
{
    public function __construct(
        private readonly MultiFileAssociationProvider $multiFileAssociationProvider,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly EntityIdHelper $entityIdHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $parentEntityClass = $context->getManageableParentEntityClass($this->doctrineHelper);
        if (!$parentEntityClass) {
            // only manageable parent entities or resources based on manageable entities are supported
            return;
        }

        $multiFileAssociationNames = $this->multiFileAssociationProvider->getMultiFileAssociationNames(
            $parentEntityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
        if (!$multiFileAssociationNames) {
            // the parent entity does not have multi file associations
            return;
        }

        $associationName = $context->getAssociationName();
        if (!\in_array($associationName, $multiFileAssociationNames, true)) {
            // it is not multi file association
            return;
        }

        $qb = $this->doctrineHelper->createQueryBuilder($context->getClassName(), 'e')
            ->innerJoin(FileItem::class, 'r', Join::WITH, 'r.file = e')
            ->innerJoin($parentEntityClass, 'p', Join::WITH, 'r MEMBER OF p.' . $associationName)
            ->orderBy('r.sortOrder');
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $qb,
            $context->getParentId(),
            $context->getParentMetadata(),
            'p',
            AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME
        );
        $context->setQuery($qb);
    }
}
