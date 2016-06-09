<?php

namespace Oro\Bundle\WorkflowBundle\Event;

class WorkflowEvents
{
    const WORKFLOW_BEFORE_UPDATE = 'oro.workflow.before_update';
    const WORKFLOW_AFTER_UPDATE = 'oro.workflow.after_update';
    const WORKFLOW_BEFORE_CREATE = 'oro.workflow.before_create';
    const WORKFLOW_AFTER_CREATE = 'oro.workflow.after_create';
    const WORKFLOW_AFTER_DELETE = 'oro.workflow.after_delete';
    const WORKFLOW_ACTIVATED = 'oro.workflow.activated';
    const WORKFLOW_DEACTIVATED = 'oro.workflow.deactivated';
}
