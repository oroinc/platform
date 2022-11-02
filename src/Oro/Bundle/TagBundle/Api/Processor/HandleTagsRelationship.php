<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\TagBundle\Api\Form\Handler\TagsAssociationHandler;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles "tags" association of taggable entities
 * for "add_relationship", "delete_relationship" and "update_relationship" actions.
 */
class HandleTagsRelationship implements ProcessorInterface
{
    private TaggableHelper $taggableHelper;
    private TagsAssociationHandler $handler;

    public function __construct(TaggableHelper $taggableHelper, TagsAssociationHandler $handler)
    {
        $this->taggableHelper = $taggableHelper;
        $this->handler = $handler;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        if (!$this->taggableHelper->isTaggable($context->getParentClassName())) {
            return;
        }

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
