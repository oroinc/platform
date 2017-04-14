<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Retrieves attribute fields saved values from form and adds them to init data
 * Keeps already defined attribute fields data in context's init data
 */
class DefaultFromStartWorkflowDataProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $transition = $context->getTransition();
        $attributeNames = array_keys($transition->getFormOptions()['attribute_fields']);

        $data = $context->getForm()->getData()->getValues($attributeNames);
        $initData = $context->get(TransitionContext::INIT_DATA) ?: [];

        $context->set(
            TransitionContext::INIT_DATA,
            array_merge(
                $data,
                $initData
            )
        );
    }
}
