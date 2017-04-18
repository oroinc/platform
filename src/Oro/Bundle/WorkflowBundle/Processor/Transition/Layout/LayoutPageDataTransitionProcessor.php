<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutPageResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class LayoutPageDataTransitionProcessor implements ProcessorInterface
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

        $context->setResult(
            [
                'workflowName' => $context->getWorkflowName(),
                'transitionName' => $context->getTransitionName(),
                'data' => [
                    'transitionFormView' => $context->getForm()->createView(),
                    'transition' => $context->getTransition(),
                    'workflowItem' => $context->getWorkflowItem(),
                    'formRouteName' => $resultType->getFormRouteName(),
                    'originalUrl' => $context->getRequest()->get('originalUrl', '/'),
                ]
            ]
        );
        $context->setProcessed(true);
    }
}
