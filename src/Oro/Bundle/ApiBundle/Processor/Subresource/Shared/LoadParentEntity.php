<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads the parent entity from the database.
 */
class LoadParentEntity implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityLoader */
    protected $entityLoader;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityLoader   $entityLoader
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityLoader $entityLoader)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
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

        $parentEntityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getParentClassName(),
            $context->getParentConfig()
        );
        if (!$parentEntityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $parentEntity = $this->entityLoader->findEntity(
            $parentEntityClass,
            $context->getParentId(),
            $context->getParentMetadata()
        );
        $context->setParentEntity($parentEntity);
    }
}
