<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class WorkflowItemSubscriber implements EventSubscriber
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

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
    public function getSubscribedEvents()
    {
        return array(
            // @codingStandardsIgnoreStart
            Events::postPersist
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
}
