<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Extension\AbstractButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Extension\StartTransitionButtonProviderExtension;

class StartTransitionButtonProviderExtensionTest extends StartTransitionButtonProviderExtensionTestCase
{
    /**
     * @return string
     */
    protected function getApplication()
    {
        return CurrentApplicationProviderInterface::DEFAULT_APPLICATION;
    }

    /**
     * @return AbstractButtonProviderExtension
     */
    protected function createExtension()
    {
        return new StartTransitionButtonProviderExtension(
            $this->workflowRegistry,
            $this->routeProvider,
            $this->originalUrlProvider
        );
    }
}
