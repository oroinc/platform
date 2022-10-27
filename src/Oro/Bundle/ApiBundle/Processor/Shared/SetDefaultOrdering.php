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
    public const OPERATION_NAME = 'set_default_ordering';

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the access validation was already performed
            return;
        }

        $criteria = $context->getCriteria();
        if (null !== $criteria && !$criteria->getOrderings()) {
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
                $this->setOrderBy($criteria, $idFieldNames);
            }
        }
        $context->setProcessed(self::OPERATION_NAME);
    }

    private function setOrderBy(Criteria $criteria, array $idFieldNames): void
    {
        $ordering = [];
        foreach ($idFieldNames as $propertyPath) {
            $ordering[$propertyPath] = Criteria::ASC;
        }
        $criteria->orderBy($ordering);
    }
}
