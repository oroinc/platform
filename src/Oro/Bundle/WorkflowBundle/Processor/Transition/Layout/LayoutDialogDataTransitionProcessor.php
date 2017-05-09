<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class LayoutDialogDataTransitionProcessor implements ProcessorInterface
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
