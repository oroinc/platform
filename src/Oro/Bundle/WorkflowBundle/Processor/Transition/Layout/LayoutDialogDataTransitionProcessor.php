<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Processes transition context to prepare layout dialog result data.
 *
 * This processor builds the result data structure for dialog-based workflow transitions,
 * including transition information, form view, and workflow item details.
 */
class LayoutDialogDataTransitionProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        $resultType = $context->getResultType();

        if (!$resultType instanceof LayoutDialogResultType) {
            return;
        }

        $context->setResult(
            [
                'data' => [
                    'transition' => $context->getTransition(),
                    'transitionFormView' => $context->getForm()->createView(),
                    'workflowItem' => $context->getWorkflowItem(),
                    'formRouteName' => $resultType->getFormRouteName(),
                    'originalUrl' => null
                ]
            ]
        );
        $context->setProcessed(true);
    }
}
