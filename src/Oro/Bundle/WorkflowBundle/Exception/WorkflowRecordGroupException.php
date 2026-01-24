<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when an error occurs while processing workflow record groups.
 *
 * This exception indicates that a workflow record group operation failed, typically due to
 * invalid group configuration or data inconsistencies.
 */
class WorkflowRecordGroupException extends WorkflowException
{
}
