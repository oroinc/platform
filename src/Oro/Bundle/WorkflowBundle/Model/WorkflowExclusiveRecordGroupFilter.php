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
        $runningWorkflowNames = $this->getRunningWorkflowNames($context->getEntity());

        if (count($runningWorkflowNames) === 0) {
            return $workflows;
        }

        $busyGroups = [];

        return $workflows->filter(
            function (Workflow $workflow) use (&$busyGroups, &$runningWorkflowNames) {
                $workflowRecordGroups = $workflow->getDefinition()->getExclusiveRecordGroups();
                foreach ($workflowRecordGroups as $workflowRecordGroup) {
                    if (in_array($workflowRecordGroup, $busyGroups, true)) {
                        return false;
                    }

                    if (in_array($workflow->getName(), $runningWorkflowNames, true)) {
                        $busyGroups[] = $workflowRecordGroup;
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
