<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Initializes context for workflow start transitions.
 *
 * This processor is executed only for start transitions and initializes the transition context
 * with the entity ID from the request parameters and an empty initialization data array.
 * The entity ID is used to determine whether to create a new entity or load an existing one
 * for the workflow to operate on.
 */
class StartContextInitProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        if (!$context->isStartTransition()) {
            return;
        }
        $context->set(TransitionContext::ENTITY_ID, $context->getRequest()->get('entityId', null));
        $context->set(TransitionContext::INIT_DATA, []);
    }
}
