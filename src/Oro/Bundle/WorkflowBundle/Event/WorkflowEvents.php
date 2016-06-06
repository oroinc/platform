<?php

namespace Oro\Bundle\WorkflowBundle\Event;

class WorkflowEvents
{
    const WORKFLOW_BEFORE_UPDATE = 'workflowBeforeUpdate';
    const WORKFLOW_UPDATED = 'workflowUpdated';
    const WORKFLOW_BEFORE_CREATE = 'workflowBeforeCreate';
    const WORKFLOW_CREATED = 'workflowCreated';
    const WORKFLOW_DELETED = 'workflowDeleted';
}