<?php

namespace Oro\Bundle\ActionBundle\Event;

/**
 * Operation event that is triggered before the execution.
 */
final class OperationPreExecuteEvent extends OperationAllowanceEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'pre_execute';
    }
}
