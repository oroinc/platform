<?php

namespace Oro\Bundle\WorkflowBundle\Serializer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

/**
 * Serializes and deserializes workflow data with workflow context awareness.
 *
 * This serializer extends the base serializer to provide workflow-specific serialization
 * capabilities, enabling proper handling of workflow attributes and data.
 */
class WorkflowDataSerializer extends Serializer implements
    WorkflowAwareSerializer,
    AttributeTypeRestrictionAwareSerializer
{
    /**
     * @var string
     */
    protected $workflowName;

    private array $restrictedTypes = [];

    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    public function setWorkflowRegistry(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @return Workflow
     */
    public function getWorkflow()
    {
        return $this->workflowRegistry->getWorkflow($this->getWorkflowName());
    }

    /**
     * @param string $workflowName
     */
    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;
    }

    /**
     * @return string
     */
    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    #[\Override]
    public function setRestrictedTypes(array $restrictedTypes): void
    {
        $this->restrictedTypes = $restrictedTypes;
    }

    #[\Override]
    public function isRestrictedType(string $type): bool
    {
        return \in_array($type, $this->restrictedTypes, true);
    }
}
