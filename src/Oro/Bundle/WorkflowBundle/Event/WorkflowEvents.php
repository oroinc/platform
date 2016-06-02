<?php
/**
 * Created by PhpStorm.
 * User: Matey
 * Date: 02.06.2016
 * Time: 15:17
 */

namespace Oro\Bundle\WorkflowBundle\Event;

class WorkflowEvents
{
    const WORKFLOW_BEFORE_UPDATE = 'workflowBeforeUpdate';
    const WORKFLOW_UPDATED = 'workflowUpdated';
    const WORKFLOW_BEFORE_CREATE = 'workflowBeforeCreate';
    const WORKFLOW_CREATED = 'workflowCreated';
    const WORKFLOW_DELETED = 'workflowDeleted';
}