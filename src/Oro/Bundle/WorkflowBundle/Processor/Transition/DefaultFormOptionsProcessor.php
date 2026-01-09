<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares default form options and data for workflow transitions without custom form configuration.
 *
 * This processor is executed when a transition does not have a custom form configuration.
 * It sets the form data to the workflow item's data and merges the transition's form options
 * with additional context options such as the workflow item and transition name, which are
 * commonly needed by form types during rendering and validation.
 */
class DefaultFormOptionsProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
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
