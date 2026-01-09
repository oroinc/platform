<?php

namespace Oro\Bundle\WorkflowBundle\Serializer;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Defines the contract for serializers that are aware of and can manage a specific workflow.
 *
 * Implementations extend the Symfony serializer interface to provide workflow-specific
 * serialization capabilities with access to the associated workflow.
 */
interface WorkflowAwareSerializer extends SerializerInterface
{
    /**
     * @return Workflow
     */
    public function getWorkflow();

    /**
     * @return string
     */
    public function getWorkflowName();

    /**
     * @param string $name
     */
    public function setWorkflowName($name);
}
