<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionDataProvider
{
    /** @var WorkflowManager */
    private $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * Get start transitions data for view based on workflow and entity.
     *
     * @param Workflow $workflow
     * @param object $entity
     * @param bool $showDisabled
     *
     * @return array
     */
    public function getAvailableStartTransitionsData(Workflow $workflow, $entity, $showDisabled)
    {
        $transitionsData = [];

        $transitions = $workflow->getTransitionManager()->getStartTransitions();
        $workflowItem = $workflow->createWorkflowItem($entity);

        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $transitionData = $this->getStartTransitionData($workflow, $transition, $entity);
                if ($transitionData !== null) {
                    $transitionsData[$transition->getName()] = $transitionData;
                }
            }
        }

        // extra case to show start transition
        if (empty($transitionsData) && $workflow->getStepManager()->hasStartStep()) {
            $defaultStartTransition = $workflow->getTransitionManager()->getDefaultStartTransition();
            if ($defaultStartTransition) {
                $startTransitionData = $this->getStartTransitionData($workflow, $defaultStartTransition, $entity);
                if ($startTransitionData !== null) {
                    $transitionsData[$defaultStartTransition->getName()] = $startTransitionData;
                } elseif ($showDisabled) {
                    $transitionsData[$defaultStartTransition->getName()] = [
                        'workflow' => $workflow,
                        'transition' => $defaultStartTransition,
                        'isAllowed' => false,
                        'errors' => new ArrayCollection()
                    ];
                }
            }
        }

        return $transitionsData;
    }

    /**
     * Get transitions data for view based on workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @return array
     */
    public function getAvailableTransitionsDataByWorkflowItem(WorkflowItem $workflowItem)
    {
        $transitionsData = [];
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $errors = new ArrayCollection();
                $isAllowed = $this->workflowManager->isTransitionAvailable($workflowItem, $transition, $errors);
                if ($isAllowed || !$transition->isUnavailableHidden()) {
                    $transitionsData[$transition->getName()] = [
                        'workflow' => $this->workflowManager->getWorkflow($workflowItem),
                        'workflowItem' => $workflowItem,
                        'transition' => $transition,
                        'isAllowed' => $isAllowed,
                        'errors' => $errors
                    ];
                }
            }
        }

        return $transitionsData;
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param object $entity
     * @return array|null
     */
    protected function getStartTransitionData(Workflow $workflow, Transition $transition, $entity)
    {
        $errors = new ArrayCollection();
        $isAllowed = $workflow->isStartTransitionAvailable($transition, $entity, [], $errors);
        $isShown = $isAllowed || !$transition->isUnavailableHidden();

        if ($isShown && $transition->isEmptyInitOptions()) {
            return [
                'workflow' => $workflow,
                'transition' => $transition,
                'isAllowed' => $isAllowed,
                'errors' => $errors
            ];
        }

        return null;
    }
}
