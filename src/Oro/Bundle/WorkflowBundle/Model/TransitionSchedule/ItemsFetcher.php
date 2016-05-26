<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class ItemsFetcher
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var TransitionQueryFactory */
    private $queryFactory;

    /**
     * @param TransitionQueryFactory $queryFactory
     * @param WorkflowManager $workflowManager
     */
    public function __construct(TransitionQueryFactory $queryFactory, WorkflowManager $workflowManager)
    {
        $this->queryFactory = $queryFactory;
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @return array an array of integers as ids of matched workflowItems
     */
    public function fetchWorkflowItemsIds($workflowName, $transitionName)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowName);

        $query = $this->queryFactory->create(
            $this->getRelatedSteps($workflow->getStepManager(), $transitionName),
            $workflow->getDefinition()->getRelatedEntity(),
            $workflow->getTransitionManager()->getTransition($transitionName)->getScheduleFilter()
        );

        $result = $query->getArrayResult();

        $ids = [];
        foreach ($result as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    /**
     * @param StepManager $stepManager
     * @param $transitionName
     * @return array
     */
    private function getRelatedSteps(StepManager $stepManager, $transitionName)
    {
        $relatedSteps = [];
        foreach ($stepManager->getRelatedTransitionSteps($transitionName) as $step) {
            $relatedSteps[] = $step->getName();
        }

        return $relatedSteps;
    }
}
