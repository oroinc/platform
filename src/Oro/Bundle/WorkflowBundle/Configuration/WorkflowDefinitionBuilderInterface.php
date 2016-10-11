<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

interface WorkflowDefinitionBuilderInterface
{
    /**
     * @param WorkflowDefinition $definition
     * @param array $configuration
     * @return void
     */
    public function build(WorkflowDefinition $definition, array &$configuration);
}
