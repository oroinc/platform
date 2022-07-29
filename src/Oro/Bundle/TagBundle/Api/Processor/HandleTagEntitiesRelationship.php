<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\TagBundle\Api\Form\Handler\TagEntitiesAssociationHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles "entities" association of Tag entity
 * for "add_relationship", "delete_relationship" and "update_relationship" actions.
 */
class HandleTagEntitiesRelationship implements ProcessorInterface
{
    private TagEntitiesAssociationHandler $handler;

    public function __construct(TagEntitiesAssociationHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        switch ($context->getAction()) {
            case ApiAction::ADD_RELATIONSHIP:
                $this->handler->handleAdd($context->getForm(), $context->getAssociationName());
                break;
            case ApiAction::DELETE_RELATIONSHIP:
                $this->handler->handleDelete($context->getForm(), $context->getAssociationName());
                break;
            case ApiAction::UPDATE_RELATIONSHIP:
                $this->handler->handleUpdate($context->getForm(), $context->getAssociationName());
                break;
        }
    }
}
