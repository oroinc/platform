<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ContextInterface as ChainProcessorContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiFormBuilderSubscriberProcessor implements ProcessorInterface
{
    /**
     * @var EventSubscriberInterface
     */
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
    public function process(ChainProcessorContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return;
        }

        if (false === $context->hasFormBuilder()) {
            return;
        }

        if ($context->hasForm()) {
            // the form is already built
            return;
        }

        $context
            ->getFormBuilder()
            ->addEventSubscriber($this->eventSubscriber);
    }
}
