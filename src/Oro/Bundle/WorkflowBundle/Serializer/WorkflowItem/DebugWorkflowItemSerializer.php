<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Checks that the serialized WorkflowResult does not contains objects.
 * This check is enabled only in debug mode.
 */
class DebugWorkflowItemSerializer implements WorkflowItemSerializerInterface
{
    private WorkflowItemSerializerInterface $innerSerializer;

    public function __construct(WorkflowItemSerializerInterface $innerSerializer)
    {
        $this->innerSerializer = $innerSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(WorkflowItem $workflowItem): array
    {
        $serializedWorkflowItem = $this->innerSerializer->serialize($workflowItem);
        $serializedWorkflowResult = $serializedWorkflowItem['result'] ?? null;
        if ($serializedWorkflowResult) {
            $this->assertNoObjects($serializedWorkflowResult, '');
        }

        return $serializedWorkflowItem;
    }

    private function assertNoObjects(array $values, string $path): void
    {
        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                $this->assertNoObjects($value, $this->buildPath($path, (string)$key));
            } elseif (\is_object($value)) {
                throw new \LogicException(sprintf(
                    'The serialized workflow result must not contain objects, but "%s" found by the path "%s".',
                    \get_class($value),
                    $this->buildPath($path, (string)$key)
                ));
            }
        }
    }

    private function buildPath(string $parentPath, string $item): string
    {
        return $parentPath
            ? $parentPath . '.' . $item
            : $item;
    }
}
