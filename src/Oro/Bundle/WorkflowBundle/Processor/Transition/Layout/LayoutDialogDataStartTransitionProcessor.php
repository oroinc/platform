<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares context data for start transition.
 */
class LayoutDialogDataStartTransitionProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $resultType = $context->getResultType();

        if (!$resultType instanceof LayoutDialogResultType) {
            return;
        }

        $context->setResult(
            [
                'data' => [
                    'workflowName' => $context->getWorkflowName(),
                    'workflowItem' => $context->getWorkflowItem(),
                    'transition' => $context->getTransition(),
                    'transitionName' => $context->getTransitionName(),
                    'transitionFormView' => $context->getForm()->createView(),
                    'entityId' => $context->getRequest()->get('entityId', 0),
                    'entityClass' => $context->getRequest()->get('entityClass'),
                    'formRouteName' => $resultType->getFormRouteName(),
                    'originalUrl' => null
                ]
            ]
        );
        $context->setProcessed(true);
    }
}
