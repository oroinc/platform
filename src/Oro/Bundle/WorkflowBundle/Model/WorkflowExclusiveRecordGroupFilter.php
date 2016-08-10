<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class WorkflowExclusiveRecordGroupFilter implements WorkflowApplicabilityFilterInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var WorkflowItemRepository
     */
    private $itemsRepository;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(ArrayCollection $workflows, WorkflowRecordContext $context)
    {
        /** @var Workflow[]|ArrayCollection $workflows */

        $runningWorkflowNames = $this->getRunningWorkflowNames($context->getEntity());

        if (count($runningWorkflowNames) === 0) {
            return $workflows;
        }

        $busyGroups = $this->getBusyGroups($workflows, $runningWorkflowNames);

        return $workflows->filter(
            function (Workflow $workflow) use (&$busyGroups) {
                $workflowRecordGroups = $workflow->getDefinition()->getExclusiveRecordGroups();
                foreach ($workflowRecordGroups as $workflowRecordGroup) {
                    if (!array_key_exists($workflowRecordGroup, $busyGroups)) {
                        continue;
                    }

                    if ($busyGroups[$workflowRecordGroup] !== $workflow->getName()) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * @param object $entity
     * @return array|string[] prioritized array of workflow names
     */
    private function getRunningWorkflowNames($entity)
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $repository = $this->getItemsRepository();

        return array_map(
            function (WorkflowItem $workflowItem) {
                return $workflowItem->getWorkflowName();
            },
            $repository->findAllByEntityMetadata($entityClass, $identifier)
        );
    }


    /**
     * @param ArrayCollection $workflows
     * @param $runningWorkflowNames
     * @return array
     */
    private function getBusyGroups(ArrayCollection $workflows, $runningWorkflowNames)
    {
        $runningExclusiveGroups = [];
        //reversing as they were fetched by priority, so last should override group as more prioritised
        foreach (array_reverse($runningWorkflowNames) as $runningWorkflowName) {
            if ($workflows->containsKey($runningWorkflowName)) {
                $groups = (array)$workflows[$runningWorkflowName]->getDefinition()->getExclusiveRecordGroups();
                if (count($groups)) {
                    foreach ($groups as $group) {
                        $runningExclusiveGroups[$group] = $runningWorkflowName;
                    }
                }
            }
        }

        return $runningExclusiveGroups;
    }

    /**
     * @return WorkflowItemRepository
     */
    private function getItemsRepository()
    {
        if (null === $this->itemsRepository) {
            $this->itemsRepository = $this->doctrineHelper->getEntityRepository(WorkflowItem::class);
        }

        return $this->itemsRepository;
    }
}
