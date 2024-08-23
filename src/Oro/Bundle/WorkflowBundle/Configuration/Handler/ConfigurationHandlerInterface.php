<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

/**
 * Configuration handlers are used to modify/handle workflow configuration changed via workflow management UI.
 */
interface ConfigurationHandlerInterface
{
    /**
     * Handle workflow configuration
     *
     * @param array $configuration
     * @return array
     */
    public function handle(array $configuration);
}
