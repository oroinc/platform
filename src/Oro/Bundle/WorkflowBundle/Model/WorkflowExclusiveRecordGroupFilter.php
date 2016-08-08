<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Provide filtering logic for Workflows that has exclusive groups conflicts in currently running entity record context
 * @package Oro\Bundle\WorkflowBundle\Model
 */
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

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(ArrayCollection $workflows, WorkflowRecordContext $context)
    {
        $entity = $context->getEntity();

        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $existingRecordsInGroups = [];
        foreach ($this->getItemsRepository()->findAllByEntityMetadata($entityClass, $identifier) as $workflowItem) {
            //todo think about getDefinition fetching resource consumption
            $groups = $workflowItem->getDefinition()->getExclusiveRecordGroups();
            if (count($groups) !== 0) {
                $name = $workflowItem->getWorkflowName();
                foreach ($groups as $group) {
                    $existingRecordsInGroups[$group] = $name;
                }
            }
        }

        return $workflows->filter(
            function (Workflow $workflow) use (&$existingRecordsInGroups) {
                $workflowRecordGroups = $workflow->getDefinition()->getExclusiveRecordGroups();
                foreach ($workflowRecordGroups as $workflowRecordGroup) {
                    if (!array_key_exists($workflowRecordGroup, $existingRecordsInGroups)) {
                        continue;
                    }

                    if ($existingRecordsInGroups[$workflowRecordGroup] !== $workflow->getName()) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|WorkflowItemRepository
     */
    private function getItemsRepository()
    {
        if (null === $this->itemsRepository) {
            $this->itemsRepository = $this->doctrineHelper->getEntityRepository(WorkflowItem::class);
        }

        return $this->itemsRepository;
    }
}
