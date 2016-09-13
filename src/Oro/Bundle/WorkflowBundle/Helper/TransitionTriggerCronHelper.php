<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionQueryFactory;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionTriggerCronHelper
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
     * @param TransitionTriggerCron $trigger
     * @return array an array of integers as ids of matched workflowItems
     */
    public function fetchWorkflowItemsIdsForTrigger(TransitionTriggerCron $trigger)
    {
        $workflow = $this->workflowManager->getWorkflow($trigger->getWorkflowDefinition()->getName());

        $query = $this->queryFactory->create(
            $workflow,
            $trigger->getTransitionName(),
            $trigger->getFilter()
        );

        return array_column($query->getArrayResult(), 'id');
    }
}
