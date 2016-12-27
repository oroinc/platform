<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Button\TransitionButton;
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
            $isAvailable = $workflow->isTransitionAllowed($workflowItem, $transition, $errors);
        } catch (\Exception $e) {
            $isAvailable = false;
            if (null !== $errors) {
                $errors->add(['message' => $e->getMessage(), 'parameters' => []]);
            }
        }

        return $isAvailable;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTransitions(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        if ($searchContext->getDatagrid() &&
            in_array($searchContext->getDatagrid(), $workflow->getDefinition()->getDatagrids(), true) &&
            $workflow->getDefinition()->getRelatedEntity() === $searchContext->getEntityClass()
        ) {
            $transitions = $workflow->getTransitionManager()->getTransitions()->toArray();

            return array_filter($transitions, function (Transition $transition) {
                return !$transition->isStart();
            });
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
}
