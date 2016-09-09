<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads the parent entity from the database.
 */
class LoadParentEntity implements ProcessorInterface
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
        /** @var SubresourceContext $context */

        if ($context->hasParentEntity()) {
            // the parent entity is already loaded
            return;
        }

        $parentEntityClass = $context->getParentClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($parentEntityClass)) {
            // only manageable entities are supported
            return;
        }

        $parentEntity = $this->doctrineHelper
            ->getEntityRepositoryForClass($parentEntityClass)
            ->find($context->getParentId());
        $context->setParentEntity($parentEntity);
    }
}
