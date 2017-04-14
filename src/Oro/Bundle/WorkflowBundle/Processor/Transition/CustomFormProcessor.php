<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class CustomFormProcessor implements ProcessorInterface
{
    /** @var FormHandlerRegistry */
    private $formHandlerRegistry;

    /**
     * @param FormHandlerRegistry $formHandlerRegistry
     */
    public function __construct(FormHandlerRegistry $formHandlerRegistry)
    {
        $this->formHandlerRegistry = $formHandlerRegistry;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
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
