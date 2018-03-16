<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;

class WorkflowItemListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var WorkflowManagerRegistry */
    protected $workflowManagerRegistry;

    /** @var WorkflowEntityConnector */
    protected $entityConnector;

    /** @var WorkflowAwareCache */
    protected $cache;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WorkflowManagerRegistry $workflowManagerRegistry
     * @param WorkflowEntityConnector $entityConnector
     * @param WorkflowAwareCache $cache
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WorkflowManagerRegistry $workflowManagerRegistry,
        WorkflowEntityConnector $entityConnector,
        WorkflowAwareCache $cache
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->workflowManagerRegistry = $workflowManagerRegistry;
        $this->entityConnector = $entityConnector;
        $this->cache = $cache;
    }

    /**
     * Set workflow item entity ID
     *
     * @param WorkflowItem       $workflowItem
     * @param LifecycleEventArgs $args
     *
     * @throws WorkflowException
     */
    public function postPersist(WorkflowItem $workflowItem, LifecycleEventArgs $args)
    {
        if (!$workflowItem->getEntityId()) {
            $entity = $workflowItem->getEntity();
            if (!$entity) {
                throw new WorkflowException('Workflow item does not contain related entity');
            }
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

            if (null === $entityId) {
                throw new WorkflowException(
                    sprintf(
                        'Workflow "%s" can not be started because ID of related entity is null',
                        $workflowItem->getWorkflowName()
                    )
                );
            }

            $workflowItem->setEntityId($entityId);

            $entityClass = $this->doctrineHelper->getEntityClass($entity);
            $workflowItem->setEntityClass($entityClass);

            $unitOfWork = $args->getEntityManager()->getUnitOfWork();
            $unitOfWork->scheduleExtraUpdate(
                $workflowItem,
                [
                    'entityId' => [null, $entityId],
                    'entityClass' => [null, $entityClass]
                ]
            );
        }
    }

    /**
     * Remove related workflow items
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$this->entityConnector->isApplicableEntity($entity) || !$this->cache->hasRelatedWorkflows($entity)) {
            return;
        }

        $workflowItems = $this->workflowManagerRegistry->getManager('system')->getWorkflowItemsByEntity($entity);

        if (count($workflowItems) > 0) {
            $em = $args->getEntityManager();
            foreach ($workflowItems as $workflowItem) {
                $em->remove($workflowItem);
            }
        }
    }
}
