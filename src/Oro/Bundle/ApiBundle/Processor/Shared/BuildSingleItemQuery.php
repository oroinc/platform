<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;

/**
 * Builds ORM QueryBuilder object that will be used to get an entity by its identifier.
 */
class BuildSingleItemQuery implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CriteriaConnector */
    protected $criteriaConnector;

    /** @var EntityIdHelper */
    protected $entityIdHelper;

    /**
     * @param DoctrineHelper    $doctrineHelper
     * @param CriteriaConnector $criteriaConnector
     * @param EntityIdHelper    $entityIdHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CriteriaConnector $criteriaConnector,
        EntityIdHelper $entityIdHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->criteriaConnector = $criteriaConnector;
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // data already exist
            return;
        }

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities or resources based on manageable entities are supported
            $entityClass = $context->getConfig()->getParentResourceClass();
            if (!$entityClass || !$this->doctrineHelper->isManageableEntityClass($entityClass)) {
                return;
            }
        }

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $query,
            $context->getId(),
            $context->getMetadata()
        );

        $criteria = $context->getCriteria();
        if (null !== $criteria) {
            $this->criteriaConnector->applyCriteria($query, $criteria);
        }

        $context->setQuery($query);
    }
}
