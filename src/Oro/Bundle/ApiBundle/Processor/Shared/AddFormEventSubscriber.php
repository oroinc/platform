<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * Adds an event subscriber to a form builder from the Context.
 */
class AddFormEventSubscriber implements ProcessorInterface
{
    /** @var EventSubscriberInterface */
    private $eventSubscriber;

    /**
     * @param EventSubscriberInterface $eventSubscriber
     */
    public function __construct(EventSubscriberInterface $eventSubscriber)
    {
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $formBuilder = $context->getFormBuilder();
        if (null !== $formBuilder) {
            $formBuilder->addEventSubscriber($this->eventSubscriber);
        }
    }
}
