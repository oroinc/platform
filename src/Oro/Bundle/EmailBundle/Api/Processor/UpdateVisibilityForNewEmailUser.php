<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Event\EmailUserAdded;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Updates a value of "private" field for a new EmailUser entity.
 */
class UpdateVisibilityForNewEmailUser implements ProcessorInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->isPrimaryEntityRequest()) {
            /**
             * do nothing when the creation of EmailUser entity is a part of Email entity creation or update
             * because the visibility is updated by {@see \Oro\Bundle\EmailBundle\Builder\EmailEntityBatchProcessor}
             */
            return;
        }

        $this->eventDispatcher->dispatch(new EmailUserAdded($context->getData()), EmailUserAdded::NAME);
    }
}
