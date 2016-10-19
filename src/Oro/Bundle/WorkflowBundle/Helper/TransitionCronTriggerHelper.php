<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class TransitionCronTriggerHelper
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var WorkflowAwareEntityFetcher */
    private $entityFetcher;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WorkflowAwareEntityFetcher $entityFetcher
     */
    public function __construct(DoctrineHelper $doctrineHelper, WorkflowAwareEntityFetcher $entityFetcher)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityFetcher = $entityFetcher;
    }

    /**
     * @param TransitionCronTrigger $trigger
     * @param Workflow $workflow
     * @return array|\object[]
     */
    public function fetchEntitiesWithoutWorkflowItems(TransitionCronTrigger $trigger, Workflow $workflow)
    {
        return $this->entityFetcher->getEntitiesWithoutWorkflowItem($workflow->getDefinition(), $trigger->getFilter());
    }

    /**
     * @param TransitionCronTrigger $trigger
     * @param Workflow $workflow
     * @return array an array of integers as ids of matched workflowItems
     */
    public function fetchWorkflowItemsForTrigger(TransitionCronTrigger $trigger, Workflow $workflow)
    {
        $steps = $workflow->getStepManager()
            ->getRelatedTransitionSteps($trigger->getTransitionName())
            ->map(
                function (Step $step) {
                    return $step->getName();
                }
            );

        $entityClass = $workflow->getDefinition()->getRelatedEntity();

        return $this->getWorkflowItemRepository()
            ->findByStepNamesAndEntityClass(
                $steps,
                $entityClass,
                $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass),
                $trigger->getFilter()
            );
    }

    /**
     * @return WorkflowItemRepository
     */
    protected function getWorkflowItemRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(WorkflowItem::class);
    }
}
