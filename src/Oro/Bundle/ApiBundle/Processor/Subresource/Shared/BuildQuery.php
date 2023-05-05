<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get a list of entities.
 */
class BuildQuery implements ProcessorInterface
{
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
        /** @var SubresourceContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $query = null;
        $parentConfig = $context->getParentConfig();
        if (null !== $parentConfig) {
            $associationField = $parentConfig->getField($context->getAssociationName());
            if (null !== $associationField) {
                $associationQuery = $associationField->getAssociationQuery();
                if (null !== $associationQuery) {
                    $query = clone $associationQuery;
                }
            }
        }
        if (null === $query) {
            $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        }

        $context->setQuery($query);
    }
}
