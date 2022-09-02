<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\TagBundle\Api\Form\Handler\TagEntitiesAssociationHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles "entities" association of Tag entity for "create" and "update" actions.
 */
class HandleTagEntitiesAssociation implements ProcessorInterface
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
        /** @var CustomizeFormDataContext $context */

        $this->handler->handleUpdate($context->getForm(), 'entities', true);
    }
}
