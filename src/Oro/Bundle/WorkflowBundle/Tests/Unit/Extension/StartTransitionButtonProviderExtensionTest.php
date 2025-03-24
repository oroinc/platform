<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Extension\StartTransitionButtonProviderExtension;

class StartTransitionButtonProviderExtensionTest extends StartTransitionButtonProviderExtensionTestCase
{
    #[\Override]
    protected function getApplication(): string
    {
        return CurrentApplicationProviderInterface::DEFAULT_APPLICATION;
    }

    #[\Override]
    protected function createExtension(): StartTransitionButtonProviderExtension
    {
        return new StartTransitionButtonProviderExtension(
            $this->workflowRegistry,
            $this->routeProvider,
            $this->originalUrlProvider
        );
    }
}
