<?php

namespace Oro\Bundle\WorkflowBundle\Event;

class WorkflowEvents
{
    const WORKFLOW_BEFORE_UPDATE = 'workflowBeforeUpdate';
    const WORKFLOW_AFTER_UPDATE = 'workflowAfterUpdate';
    const WORKFLOW_BEFORE_CREATE = 'workflowBeforeCreate';
    const WORKFLOW_AFTER_CREATE = 'workflowAfterCreate';
    const WORKFLOW_AFTER_DELETE = 'workflowAfterDelete';
    const WORKFLOW_ACTIVATED = 'workflowActivated';
    const WORKFLOW_DEACTIVATED = 'workflowDeactivated';
}
