<?php

namespace Oro\Bundle\WorkflowBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class TransitionCustomFormHandler implements TransitionFormHandlerInterface
{
    /** @var FormHandlerRegistry */
    protected $formHandlerRegistry;

    /**
     * {@inheritDoc}
     */
    public function __construct(FormHandlerRegistry $formHandlerRegistry)
    {
        $this->formHandlerRegistry = $formHandlerRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function processStartTransitionForm(
        FormInterface $form,
        WorkflowItem $workflowItem,
        Transition $transition,
        Request $request
    ) {
        return $this->process($form, $workflowItem, $transition, $request);
    }

    /**
     * {@inheritDoc}
     */
    public function processTransitionForm(
        FormInterface $form,
        WorkflowItem $workflowItem,
        Transition $transition,
        Request $request
    ) {
        return $this->process($form, $workflowItem, $transition, $request);
    }

    /**
     * @param FormInterface $form
     * @param WorkflowItem $workflowItem
     * @param Transition $transition
     * @param Request $request
     *
     * @return bool
     */
    protected function process(
        FormInterface $form,
        WorkflowItem $workflowItem,
        Transition $transition,
        Request $request
    ) {
        return $this->formHandlerRegistry->get($transition->getFormHandler())->process(
            $workflowItem->getData()->get($transition->getFormDataAttribute()),
            $form,
            $request
        );
    }
}
