<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowItemSubscriber implements EventSubscriber
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityConnector
     */
    protected $entityConnector;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var array
     */
    protected $entitiesScheduledForWorkflowStart = array();

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityConnector $entityConnector
     * @param WorkflowManager $workflowManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityConnector $entityConnector,
        WorkflowManager $workflowManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityConnector = $entityConnector;
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            // @codingStandardsIgnoreStart
            Events::postPersist,
            Events::preRemove,
            Events::postFlush
            // @codingStandardsIgnoreEnd
        );
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->updateWorkflowItemEntityRelation($args);
        $this->scheduleStartWorkflowForNewEntity($args);
    }

    /**
     * Schedule workflow auto start for entity.
     *
     * @param LifecycleEventArgs $args
     */
    protected function scheduleStartWorkflowForNewEntity(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $activeWorkflow = $this->workflowManager->getApplicableWorkflow($entity);
        if ($activeWorkflow && $activeWorkflow->getDefinition()->getStartStep()) {
            $this->entitiesScheduledForWorkflowStart[] = array(
                'entity' => $entity,
                'workflow' => $activeWorkflow
            );
        }
    }

    /**
     * Set workflow item entity ID
     *
     * @param LifecycleEventArgs $args
     * @throws WorkflowException
     */
    protected function updateWorkflowItemEntityRelation(LifecycleEventArgs $args)
    {
        $workflowItem = $args->getEntity();
        if ($workflowItem instanceof WorkflowItem && !$workflowItem->getEntityId()) {
            $entity = $workflowItem->getEntity();
            if (!$entity) {
                throw new WorkflowException('Workflow item does not contain related entity');
            }
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $workflowItem->setEntityId($entityId);

            $unitOfWork = $args->getEntityManager()->getUnitOfWork();
            $unitOfWork->scheduleExtraUpdate($workflowItem, array('entityId' => array(null, $entityId)));
        }
    }

    /**
     * Execute workflow start for scheduled entities.
     */
    public function postFlush()
    {
        if ($this->entitiesScheduledForWorkflowStart) {
            while ($entityData = array_shift($this->entitiesScheduledForWorkflowStart)) {
                $this->workflowManager->startWorkflow(
                    $entityData['workflow'],
                    $entityData['entity']
                );
            }
        }
    }

    /**
     * Remove related workflow item
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($this->entityConnector->isWorkflowAware($entity)) {
            $workflowItem = $this->entityConnector->getWorkflowItem($entity);
            if ($workflowItem) {
                $args->getEntityManager()->remove($workflowItem);
            }
        }
    }
}
