<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets ordering by an entity identifier to the Criteria object from the context
 * if the ordering is not set yet.
 */
class SetDefaultOrdering implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

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

        $idFieldNames = [];
        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if ($entityClass) {
            $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        } else {
            $config = $context->getConfig();
            if (null !== $config && $config->isSortingEnabled()) {
                $fieldNames = $config->getIdentifierFieldNames();
                foreach ($fieldNames as $fieldName) {
                    $idFieldNames[] = $config->getField($fieldName)->getPropertyPath($fieldName);
                }
            }
        }

        if ($idFieldNames) {
            $ordering = [];
            foreach ($idFieldNames as $propertyPath) {
                $ordering[$propertyPath] = Criteria::ASC;
            }
            $criteria->orderBy($ordering);
        }
    }
}
