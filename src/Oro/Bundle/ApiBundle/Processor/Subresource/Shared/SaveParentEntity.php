<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves all changes of the parent ORM entity to the database.
 */
class SaveParentEntity implements ProcessorInterface
{
    public const OPERATION_NAME = 'save_parent_entity';

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
        /** @var ChangeRelationshipContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the entity was already saved
            return;
        }

        $parentEntity = $context->getParentEntity();
        if (!\is_object($parentEntity)) {
            // the parent entity does not exist
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($parentEntity, false);
        if (!$em) {
            // only manageable entities are supported
            return;
        }

        $em->flush();

        $context->setProcessed(self::OPERATION_NAME);
    }
}
