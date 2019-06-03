<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\QueryFactory;

/**
 * Loads the parent entity from the database and adds it to the context.
 */
class LoadParentEntity implements ProcessorInterface
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
        /** @var ChangeRelationshipContext $context */

        if ($context->hasParentEntity()) {
            // the parent entity is already loaded
            return;
        }

        $parentEntityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getParentClassName(),
            $context->getParentConfig()
        );
        if (!$parentEntityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $qb = $this->doctrineHelper->createQueryBuilder($parentEntityClass, 'e');
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $qb,
            $context->getParentId(),
            $context->getParentMetadata()
        );
        $query = $this->queryFactory->getQuery($qb, $context->getParentConfig());

        $parentEntity = $query->getOneOrNullResult();
        if (null !== $parentEntity) {
            $context->setParentEntity($parentEntity);
        }
    }
}
