<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class StartContextInitProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$context->isStartTransition()) {
            return;
        }
        $context->set(TransitionContext::ENTITY_ID, $context->getRequest()->get('entityId', null));
        $context->set(TransitionContext::INIT_DATA, []);
    }
}
