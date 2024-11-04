<?php

namespace Oro\Bundle\WorkflowBundle\Model;

/**
 * Represents a context for filtering applicable workflows.
 */
final readonly class WorkflowRecordContext
{
    public function __construct(
        private object $entity
    ) {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
