<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowTransitionTriggersListener implements EventSubscriberInterface
{
    public function triggersCreate(WorkflowChangesEvent $event)
    {

    }

    public function triggersUpdate(WorkflowChangesEvent $event)
    {

    }

    public function triggersDelete(WorkflowChangesEvent $event)
    {

    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkflowEvents::WORKFLOW_AFTER_CREATE => 'triggersCreate',
            WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'triggersUpdate',
            WorkflowEvents::WORKFLOW_AFTER_DELETE => 'triggersDelete',
            WorkflowEvents::WORKFLOW_DEACTIVATED => 'triggersDelete',
            WorkflowEvents::WORKFLOW_ACTIVATED => 'triggersCreate'
        ];
    }
}
