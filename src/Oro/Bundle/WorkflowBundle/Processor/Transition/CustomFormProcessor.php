<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Processes custom form submission for workflow transitions.
 *
 * This processor handles the submission of custom forms defined for workflow transitions.
 * It retrieves the appropriate form handler from the registry based on the transition's form handler
 * configuration and delegates the form processing to that handler. The handler processes the form data
 * and updates the workflow item's data attribute with the processed form data.
 */
class CustomFormProcessor implements ProcessorInterface
{
    /** @var FormHandlerRegistry */
    private $formHandlerRegistry;

    public function __construct(FormHandlerRegistry $formHandlerRegistry)
    {
        $this->formHandlerRegistry = $formHandlerRegistry;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        $transition = $context->getTransition();
        $workflowItem = $context->getWorkflowItem();

        $handler = $this->formHandlerRegistry->get($transition->getFormHandler());

        $context->setSaved(
            $handler->process(
                $workflowItem->getData()->get($transition->getFormDataAttribute()),
                $context->getForm(),
                $context->getRequest()
            )
        );
    }
}
