<?php

namespace Oro\Bundle\ActionBundle\Event;

/**
 * Operation event that is triggered after the execution.
 */
final class OperationExecuteEvent extends OperationAllowanceEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'execute';
    }
}
