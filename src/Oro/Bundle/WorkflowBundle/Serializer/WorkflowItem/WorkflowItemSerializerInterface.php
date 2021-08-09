<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Represents a service to convert {@see \Oro\Bundle\WorkflowBundle\Model\WorkflowResult} to an array.
 */
interface WorkflowItemSerializerInterface
{
    /**
     * Converts the given WorkflowItem object to an array.
     */
    public function serialize(WorkflowItem $workflowItem): array;
}
