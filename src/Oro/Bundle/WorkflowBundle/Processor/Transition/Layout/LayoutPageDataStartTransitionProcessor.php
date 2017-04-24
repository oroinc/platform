<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutPageResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class LayoutPageDataStartTransitionProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $resultType = $context->getResultType();

        if (!$resultType instanceof LayoutPageResultType) {
            return;
        }

        $workflowName = $context->getWorkflowName();
        $transitionName = $context->getTransitionName();
        $request = $context->getRequest();

        $context->setResult(
            [
                'workflowName' => $workflowName,
                'transitionName' => $transitionName,
                'data' => [
                    'transitionFormView' => $context->getForm()->createView(),
                    'workflowName' => $workflowName,
                    'workflowItem' => $context->getWorkflowItem(),
                    'transitionName' => $transitionName,
                    'transition' => $context->getTransition(),
                    'entityId' => $request->get('entityId', 0),
                    'originalUrl' => $request->get('originalUrl', '/'),
                    'formRouteName' => $resultType->getFormRouteName(),
                ]
            ]
        );
        $context->setProcessed(true);
    }
}
