<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches {@see EmailBodyAdded} event when a new email has the body
 * or the body is added to an existing email.
 */
class DispatchEmailBodyAddedEvent implements ProcessorInterface
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

        /** @var Email $email */
        $email = $context->getData();
        if (null !== $email->getEmailBody() && $context->getForm()->get('body')->isSubmitted()) {
            $this->eventDispatcher->dispatch(new EmailBodyAdded($email), EmailBodyAdded::NAME);
        }
    }
}
