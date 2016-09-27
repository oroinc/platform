<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionCronTriggerHelper
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var WorkflowItemRepository */
    private $repository;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param WorkflowManager $workflowManager
     * @param WorkflowItemRepository $repository
     * @param ManagerRegistry $registry
     */
    public function __construct(
        WorkflowManager $workflowManager,
        WorkflowItemRepository $repository,
        ManagerRegistry $registry
    ) {
        $this->workflowManager = $workflowManager;
        $this->repository = $repository;
        $this->registry = $registry;
    }

    /**
     * @param TransitionCronTrigger $trigger
     * @return array an array of integers as ids of matched workflowItems
     */
    public function fetchWorkflowItemsForTrigger(TransitionCronTrigger $trigger)
    {
        $workflow = $this->workflowManager->getWorkflow($trigger->getWorkflowDefinition()->getName());

        $steps = $workflow->getStepManager()
            ->getRelatedTransitionSteps($trigger->getTransitionName())
            ->map(
                function (Step $step) {
                    return $step->getName();
                }
            );

        $entityClass = $workflow->getDefinition()->getRelatedEntity();

        return $this->repository->findByStepNamesAndEntityClass(
            $steps,
            $entityClass,
            $this->getIdentifierField($entityClass),
            $trigger->getFilter()
        );
    }

    /**
     * @param string $entityClass
     * @return string
     */
    protected function getIdentifierField($entityClass)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->registry->getManagerForClass($entityClass)->getClassMetadata($entityClass);

        return $metadata->getSingleIdentifierFieldName();
    }
}
