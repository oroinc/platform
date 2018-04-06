<?php

namespace Oro\Bundle\WorkflowBundle\Serializer;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Symfony\Component\Serializer\SerializerInterface;

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
