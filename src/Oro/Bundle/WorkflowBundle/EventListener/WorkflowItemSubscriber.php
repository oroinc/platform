<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;

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
     * @param DoctrineHelper $doctrineHelper
     * @param EntityConnector $entityConnector
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityConnector $entityConnector)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityConnector = $entityConnector;
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
            // @codingStandardsIgnoreEnd
        );
    }

    /**
     * Set workflow item entity ID
     *
     * @param LifecycleEventArgs $args
     * @throws WorkflowException
     */
    public function postPersist(LifecycleEventArgs $args)
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
