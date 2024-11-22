<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Form\EventListener\WorkflowAttributeFormInitListener;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Processes transition form, executes form_init actions.
 */
class CustomFormOptionsProcessor implements ProcessorInterface
{
    public function __construct(
        private WorkflowAttributeFormInitListener $formInitListener
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
            // Workflow item is locked here to prevent workflow transition during form_init actions call
            $workflowItem->lock();
            $this->formInitListener->executeInitActions($transition->getFormOptions()['form_init'], $workflowItem);
            $this->formInitListener->dispatchFormInitEvents($workflowItem, $transition);
            $workflowItem->unlock();
        }

        $dataAttribute = $transition->getFormDataAttribute();
        $formData = $workflowItem->getData()->get($dataAttribute);

        $context->setFormData($formData);
    }
}
