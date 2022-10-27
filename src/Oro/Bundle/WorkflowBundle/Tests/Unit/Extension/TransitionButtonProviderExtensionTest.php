<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Extension\AbstractButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension;

class TransitionButtonProviderExtensionTest extends TransitionButtonProviderExtensionTestCase
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
        return new TransitionButtonProviderExtension(
            $this->workflowRegistry,
            $this->routeProvider,
            $this->originalUrlProvider
        );
    }
}
