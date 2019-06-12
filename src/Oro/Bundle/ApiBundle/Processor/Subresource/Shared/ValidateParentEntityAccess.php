<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\QueryFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Loads the parent entity from the database and checks whether an VIEW access to it is granted.
 */
class ValidateParentEntityAccess implements ProcessorInterface
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
        /** @var SubresourceContext $context */

        if (null === $context->getParentConfig()->getField($context->getAssociationName())) {
            // skip sub-resources that do not associated with any field in the parent entity config
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
        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($parentEntityClass);
        if (\count($idFieldNames) !== 0) {
            $qb->select('e.' . \reset($idFieldNames));
        }
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $qb,
            $context->getParentId(),
            $context->getParentMetadata()
        );
        $query = $this->queryFactory->getQuery($qb, $context->getParentConfig());

        $data = $query->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$data) {
            throw new NotFoundHttpException('The parent entity does not exist.');
        }
    }
}
