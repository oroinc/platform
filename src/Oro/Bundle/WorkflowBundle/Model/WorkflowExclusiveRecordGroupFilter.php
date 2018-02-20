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
        $lockedGroup = $this->retrieveLockedGroups($workflows, $context);

        if (count($lockedGroup) === 0) {
            return $workflows;
        }

        return $workflows->filter(
            function (Workflow $workflow) use (&$lockedGroup) {
                $definition = $workflow->getDefinition();
                if ($definition->hasExclusiveRecordGroups()) {
                    $name = $workflow->getName();
                    foreach ($definition->getExclusiveRecordGroups() as $recordGroup) {
                        if (array_key_exists($recordGroup, $lockedGroup) && $lockedGroup[$recordGroup] !== $name) {
                            return false;
                        }
                    }
                }

                return true;
            }
        );
    }

    /**
     * @param ArrayCollection $workflows
     * @param WorkflowRecordContext $context
     * @return array
     */
    private function retrieveLockedGroups(ArrayCollection $workflows, WorkflowRecordContext $context)
    {
        $runningWorkflowNames = $this->getRunningWorkflowNames($context->getEntity());

        $lockedGroups = [];

        if (count($runningWorkflowNames) === 0) {
            //no locks as no workflows in progress
            return $lockedGroups;
        }

        //as workflows comes in order of its priorities then highest one must replace/override lower one
        $workflows = array_reverse($workflows->toArray());

        /**@var Workflow[] $workflows */
        foreach ($workflows as $workflow) {
            $definition = $workflow->getDefinition();
            $workflowName = $definition->getName();
            if ($definition->hasExclusiveRecordGroups() && in_array($workflowName, $runningWorkflowNames, true)) {
                foreach ($definition->getExclusiveRecordGroups() as $recordGroup) {
                    $lockedGroups[$recordGroup] = $workflowName;
                }
            }
        }

        return $lockedGroups;
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
