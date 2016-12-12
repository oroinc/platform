<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

interface WorkflowDefinitionBuilderExtensionInterface
{
    /**
     * @param string $workflowName
     * @param array $configuration
     * @return array updated configuration
     */
    public function prepare($workflowName, array $configuration);
}
