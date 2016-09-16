<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionTriggerCronHelper
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var WorkflowItemRepository */
    private $repository;

    /**
     * @param WorkflowItemRepository $repository
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager, WorkflowItemRepository $repository)
    {
        $this->workflowManager = $workflowManager;
        $this->repository = $repository;
    }

    /**
     * @param TransitionTriggerCron $trigger
     * @return array an array of integers as ids of matched workflowItems
     */
    public function fetchWorkflowItemsIdsForTrigger(TransitionTriggerCron $trigger)
    {
        $workflow = $this->workflowManager->getWorkflow($trigger->getWorkflowDefinition()->getName());

        $steps = $workflow->getStepManager()
            ->getRelatedTransitionSteps($trigger->getTransitionName())
            ->map(
                function (Step $step) {
                    return $step->getName();
                }
            );

        return $this->repository->getIdsByStepNamesAndEntityClass(
            $steps,
            $workflow->getDefinition()->getRelatedEntity(),
            $trigger->getFilter()
        );
    }
}
