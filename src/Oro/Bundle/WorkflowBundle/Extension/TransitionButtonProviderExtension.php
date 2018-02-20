<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Button\TransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class TransitionButtonProviderExtension extends AbstractButtonProviderExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(ButtonInterface $button)
    {
        return $button instanceof TransitionButton && !$button->getTransition()->isStart();
    }

    /**
     * {@inheritdoc}
     * @param TransitionButton $button
     */
    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        Collection $errors = null
    ) {
        if (!$this->supports($button)) {
            throw $this->createUnsupportedButtonException($button);
        }

        $workflowItem = $button->getWorkflow()->getWorkflowItemByEntityId($buttonSearchContext->getEntityId());

        if ($workflowItem === null) {
            return false;
        }

        $transition = $button->getTransition();
        $workflow = $button->getWorkflow();
        try {
            $isAvailable = !$transition->isHidden() &&
                $workflow->isTransitionAvailable($workflowItem, $transition, $errors) &&
                $this->validateTransitionStep($workflow, $transition, $workflowItem);
        } catch (\Exception $e) {
            $isAvailable = false;
            $this->addError($button, $e, $errors);
        }

        return $isAvailable;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTransitions(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        $transitions = $this->findByDatagrids($workflow, $searchContext);

        return array_filter($transitions, function (Transition $transition) {
            return !$transition->isStart();
        });
    }

    /**
     * @param Workflow $workflow
     * @param ButtonSearchContext $searchContext
     *
     * @return array
     */
    protected function findByDatagrids(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        if ($searchContext->getDatagrid() &&
            in_array($searchContext->getDatagrid(), $workflow->getDefinition()->getDatagrids(), true) &&
            $workflow->getDefinition()->getRelatedEntity() === $searchContext->getEntityClass()
        ) {
            return $workflow->getTransitionManager()->getTransitions()->toArray();
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransitionButton(
        Transition $transition,
        Workflow $workflow,
        ButtonContext $buttonContext
    ) {
        return new TransitionButton($transition, $workflow, $buttonContext);
    }

    /**
     * {@inheritdoc}
     */
    protected function getApplication()
    {
        return CurrentApplicationProviderInterface::DEFAULT_APPLICATION;
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     *
     * @return bool
     */
    protected function validateTransitionStep(Workflow $workflow, Transition $transition, WorkflowItem $workflowItem)
    {
        $currentStep = null;
        if ($workflowItem->getCurrentStep() && $currentStepName = $workflowItem->getCurrentStep()->getName()) {
            $currentStep = $workflow->getStepManager()->getStep($currentStepName);
        }

        if ($currentStep && $currentStep->isAllowedTransition($transition->getName())) {
            return true;
        }

        return false;
    }
}
