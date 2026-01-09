<?php

namespace Oro\Bundle\WorkflowBundle\Event;

/**
 * Defines event names for workflow lifecycle and transition operations.
 */
class WorkflowEvents
{
    public const WORKFLOW_BEFORE_UPDATE = 'oro.workflow.before_update';
    public const WORKFLOW_AFTER_UPDATE = 'oro.workflow.after_update';
    public const WORKFLOW_BEFORE_CREATE = 'oro.workflow.before_create';
    public const WORKFLOW_AFTER_CREATE = 'oro.workflow.after_create';
    public const WORKFLOW_AFTER_DELETE = 'oro.workflow.after_delete';
    public const WORKFLOW_BEFORE_ACTIVATION = 'oro.workflow.before_activation';
    public const WORKFLOW_ACTIVATED = 'oro.workflow.activated';
    public const WORKFLOW_BEFORE_DEACTIVATION = 'oro.workflow.before_deactivation';
    public const WORKFLOW_DEACTIVATED = 'oro.workflow.deactivated';
    public const NOTIFICATION_TRANSIT_EVENT = 'oro.workflow.event.notification.workflow_transition';
}
