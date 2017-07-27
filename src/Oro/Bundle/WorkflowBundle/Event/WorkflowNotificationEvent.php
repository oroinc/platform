<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;

class WorkflowNotificationEvent extends NotificationEvent
{
    /** @var WorkflowTransitionRecord */
    protected $transitionRecord;

    /** @var AbstractUser */
    protected $transitionUser;

    /**
     * @param object $entity
     * @param WorkflowTransitionRecord $transitionRecord
     * @param AbstractUser $transitionUser
     */
    public function __construct(
        $entity,
        WorkflowTransitionRecord $transitionRecord,
        AbstractUser $transitionUser = null
    ) {
        parent::__construct($entity);

        $this->transitionRecord = $transitionRecord;
        $this->transitionUser = $transitionUser;
    }

    /**
     * @return WorkflowTransitionRecord
     */
    public function getTransitionRecord()
    {
        return $this->transitionRecord;
    }

    /**
     * @return AbstractUser
     */
    public function getTransitionUser()
    {
        return $this->transitionUser;
    }
}
