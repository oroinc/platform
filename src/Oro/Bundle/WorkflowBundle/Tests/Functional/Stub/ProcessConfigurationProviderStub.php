<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Stub;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;

/**
 * Rewrites the config directory for tests.
 */
class ProcessConfigurationProviderStub extends ProcessConfigurationProvider
{
    public function setConfigDirectory(string $directory): void
    {
        $this->configDirectory = $directory;
    }

    public function getConfigDirectory(): string
    {
        return $this->configDirectory;
    }
}
