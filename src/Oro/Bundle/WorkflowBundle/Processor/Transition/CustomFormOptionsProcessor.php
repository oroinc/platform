<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class CustomFormOptionsProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $transition = $context->getTransition();
        if (!$transition->hasFormConfiguration()) {
            return;
        }

        $workflowItem = $context->getWorkflowItem();

        if (array_key_exists('form_init', $transition->getFormOptions())) {
            /** @var ActionInterface $action */
            $action = $transition->getFormOptions()['form_init'];
            $action->execute($workflowItem);
        }

        $dataAttribute = $transition->getFormDataAttribute();
        $formData = $workflowItem->getData()->get($dataAttribute);

        $context->setFormData($formData);
    }
}
