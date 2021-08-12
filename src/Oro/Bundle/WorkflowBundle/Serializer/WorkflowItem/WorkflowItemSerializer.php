<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;

/**
 * Converts {@see \Oro\Bundle\WorkflowBundle\Entity\WorkflowItem} to an array.
 */
class WorkflowItemSerializer implements WorkflowItemSerializerInterface
{
    private WorkflowResultObjectSerializerInterface $workflowResultObjectSerializer;

    public function __construct(WorkflowResultObjectSerializerInterface $workflowResultObjectSerializer)
    {
        $this->workflowResultObjectSerializer = $workflowResultObjectSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(WorkflowItem $workflowItem): array
    {
        return [
            'id'            => $workflowItem->getId(),
            'workflow_name' => $workflowItem->getWorkflowName(),
            'entity_id'     => $workflowItem->getEntityId(),
            'entity_class'  => $workflowItem->getEntityClass(),
            'result'        => $this->serializeWorkflowResult($workflowItem->getResult())
        ];
    }

    private function serializeWorkflowResult(WorkflowResult $workflowResult): ?array
    {
        $result = $this->convertToArray($workflowResult->getValues());
        if (!$result) {
            return null;
        }

        return $result;
    }

    private function convertToArray(iterable $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (is_iterable($value)) {
                $result[$key] = $this->convertToArray($value);
            } elseif (\is_object($value)) {
                $serializedObject = $this->workflowResultObjectSerializer->serialize($value);
                if (null !== $serializedObject) {
                    $result[$key] = $serializedObject;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
