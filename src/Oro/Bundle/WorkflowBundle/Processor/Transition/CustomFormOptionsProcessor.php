<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class CustomFormOptionsProcessor implements ProcessorInterface
{
    public function __construct(
        private FormInitListener $formInitListener
    ) {
    }

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
            $this->formInitListener->executeInitAction($transition->getFormOptions()['form_init'], $workflowItem);
            $this->formInitListener->dispatchFormInitEvents($workflowItem, $transition);
        }

        $dataAttribute = $transition->getFormDataAttribute();
        $formData = $workflowItem->getData()->get($dataAttribute);

        $context->setFormData($formData);
    }
}
