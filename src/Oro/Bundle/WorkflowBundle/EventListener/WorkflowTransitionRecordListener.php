<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\Migrations\Data\ORM\LoadWorkflowNotificationEvents;

class WorkflowTransitionRecordListener implements OptionalListenerInterface
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var bool */
    protected $enabled = true;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = (bool)$enabled;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $transitionRecord = $args->getEntity();

        if (!$this->enabled || !$transitionRecord instanceof WorkflowTransitionRecord) {
            return;
        }

        $this->eventDispatcher->dispatch(
            LoadWorkflowNotificationEvents::TRANSIT_EVENT,
            $this->getNotificationEvent($transitionRecord)
        );
    }

    /**
     * @param WorkflowTransitionRecord $transitionRecord
     * @return NotificationEvent
     */
    protected function getNotificationEvent(WorkflowTransitionRecord $transitionRecord)
    {
        $entity = $transitionRecord->getWorkflowItem()->getEntity();

        return new WorkflowNotificationEvent($entity, $transitionRecord);
    }
}
