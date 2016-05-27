<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
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

        $transition = $workflow->getTransitionManager()->getTransition($transitionName);

        if (!$transition instanceof Transition) {
            throw new \RuntimeException(
                sprintf('Cant get transition by given identifier "%s"', (string)$transitionName)
            );
        }

        $query = $this->queryFactory->create(
            $this->getRelatedSteps($workflow->getStepManager(), $transitionName),
            $workflow->getDefinition()->getRelatedEntity(),
            $transition->getScheduleFilter()
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
        $steps = $stepManager->getRelatedTransitionSteps($transitionName);
        foreach ($steps as $step) {
            $relatedSteps[] = $step->getName();
        }

        return $relatedSteps;
    }
}
