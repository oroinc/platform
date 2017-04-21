<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class DefaultFormOptionsProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $transition = $context->getTransition();

        if ($transition->hasFormConfiguration()) {
            return;
        }

        $workflowItem = $context->getWorkflowItem();

        $context->setFormData($workflowItem->getData());
        $context->setFormOptions(
            array_merge(
                $transition->getFormOptions(),
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName()
                ]
            )
        );
    }
}
