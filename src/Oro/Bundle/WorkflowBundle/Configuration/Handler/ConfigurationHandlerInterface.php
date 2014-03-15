<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

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
