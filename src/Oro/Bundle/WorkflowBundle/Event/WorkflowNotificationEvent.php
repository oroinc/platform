<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;

class WorkflowNotificationEvent extends NotificationEvent
{
    /** @var WorkflowTransitionRecord */
    protected $transitionRecord;

    /**
     * @param object $entity
     * @param WorkflowTransitionRecord $transitionRecord
     */
    public function __construct($entity, WorkflowTransitionRecord $transitionRecord)
    {
        parent::__construct($entity);

        $this->transitionRecord = $transitionRecord;
    }

    /**
     * @return WorkflowTransitionRecord
     */
    public function getTransitionRecord()
    {
        return $this->transitionRecord;
    }
}
