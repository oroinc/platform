<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

/**
 * Defines the contract for extending workflow definition building with custom preparation logic.
 *
 * Implementations can modify or enhance workflow configurations before they are fully processed,
 * allowing for flexible customization of workflow definitions during the build phase.
 */
interface WorkflowDefinitionBuilderExtensionInterface
{
    /**
     * @param string $workflowName
     * @param array $configuration
     * @return array updated configuration
     */
    public function prepare($workflowName, array $configuration);
}
