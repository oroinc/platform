<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Ensures that init data contains form data to correctly process start transition for saved custom form
 */
class CustomFromStartWorkflowDataProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $formDataAttribute = $context->getTransition()->getFormDataAttribute();
        $initData = $context->get(TransitionContext::INIT_DATA) ?: [];

        if (!isset($initData[$formDataAttribute])) {
            $initData[$formDataAttribute] = $context->getForm()->getData();
            $context->set(TransitionContext::INIT_DATA, $initData);
        }
    }
}
