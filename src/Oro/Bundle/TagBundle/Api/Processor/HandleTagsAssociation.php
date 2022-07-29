<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\TagBundle\Api\Form\Handler\TagsAssociationHandler;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles "tags" association of taggable entities for "create" and "update" actions.
 */
class HandleTagsAssociation implements ProcessorInterface
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
        /** @var CustomizeFormDataContext $context */

        if (!$this->taggableHelper->isTaggable($context->getClassName())) {
            return;
        }

        $this->handler->handleUpdate($context->getForm(), 'tags', true);
    }
}
