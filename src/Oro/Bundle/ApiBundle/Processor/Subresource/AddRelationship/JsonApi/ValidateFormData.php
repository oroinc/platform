<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ValidateFormData implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeRelationshipContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $parentEntityClass = $context->getParentClassName();
        $parentEntity = $this->doctrineHelper->getEntityRepositoryForClass($parentEntityClass)
            ->find($context->getParentId());
        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->criteriaConnector->applyCriteria($query, $criteria);

        $context->setQuery($query);
    }
}
