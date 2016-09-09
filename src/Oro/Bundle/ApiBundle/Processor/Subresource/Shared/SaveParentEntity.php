<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Saves all changes of the parent ORM entity to the database.
 */
class SaveParentEntity implements ProcessorInterface
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

        $parentEntity = $context->getParentEntity();
        if (!is_object($parentEntity)) {
            // the parent entity does not exist
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($parentEntity, false);
        if (!$em) {
            // only manageable entities are supported
            return;
        }

        $em->flush($parentEntity);
    }
}
