<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem;

/**
 * Represents a service to convert objects stored in
 * {@see \Oro\Bundle\WorkflowBundle\Model\WorkflowResult} to an array.
 */
interface WorkflowResultObjectSerializerInterface
{
    /**
     * Converts the given object to an array.
     * Returns NULL when the given object should not be added to the serialized result.
     */
    public function serialize(object $object): ?array;
}
