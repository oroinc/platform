<?php

namespace Oro\Bundle\WorkflowBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

interface TransitionFormHandlerInterface
{
    /**
     * Returns true on success
     *
     * @param FormInterface $form
     * @param WorkflowItem $workflowItem
     * @param Transition $transition
     * @param Request|null $request
     *
     * @return bool
     */
    public function processStartTransitionForm(
        FormInterface $form,
        WorkflowItem $workflowItem,
        Transition $transition,
        Request $request
    );

    /**
     * Returns true on success
     *
     * @param FormInterface $form
     * @param WorkflowItem $workflowItem
     * @param Transition $transition
     * @param Request|null $request
     *
     * @return bool
     */
    public function processTransitionForm(
        FormInterface $form,
        WorkflowItem $workflowItem,
        Transition $transition,
        Request $request
    );
}
