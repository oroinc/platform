<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\WorkflowItem\Stub;

use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\WorkflowResultObjectSerializerInterface;

class TestObjectWorkflowResultObjectSerializer implements WorkflowResultObjectSerializerInterface
{
    private WorkflowResultObjectSerializerInterface $innerSerializer;

    public function __construct(WorkflowResultObjectSerializerInterface $innerSerializer)
    {
        $this->innerSerializer = $innerSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(object $object): ?array
    {
        if (!$object instanceof TestObject) {
            return $this->innerSerializer->serialize($object);
        }

        if (null === $object->getCode()) {
            return null;
        }

        return ['code' => $object->getCode()];
    }
}
