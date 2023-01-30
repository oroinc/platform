<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
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

    private DoctrineHelper $doctrineHelper;
    private FlushDataHandlerInterface $flushDataHandler;

    public function __construct(DoctrineHelper $doctrineHelper, FlushDataHandlerInterface $flushDataHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->flushDataHandler = $flushDataHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
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
        if (null === $em) {
            // only manageable entities are supported
            return;
        }

        $this->flushDataHandler->flushData(
            $em,
            new FlushDataHandlerContext([$context], $context->getSharedData())
        );

        $context->setProcessed(self::OPERATION_NAME);
    }
}
