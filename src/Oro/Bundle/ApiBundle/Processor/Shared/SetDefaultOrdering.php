<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets ordering be entity identifier to the Criteria object from the context
 * for ORM entities if an ordering is not set yet.
 */
class SetDefaultOrdering implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

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
        /** @var Context $context */

        $criteria = $context->getCriteria();
        if (null === $criteria || $criteria->getOrderings()) {
            // the criteria object does not exist or ordering is already set
            return;
        }

        $entityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $context->getConfig()
        );
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $ordering = [];
        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        foreach ($idFieldNames as $propertyPath) {
            $ordering[$propertyPath] = Criteria::ASC;
        }
        $criteria->orderBy($ordering);
    }
}
