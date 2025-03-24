<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension;

class TransitionButtonProviderExtensionTest extends TransitionButtonProviderExtensionTestCase
{
    #[\Override]
    protected function getApplication(): string
    {
        return CurrentApplicationProviderInterface::DEFAULT_APPLICATION;
    }

    #[\Override]
    protected function createExtension(): TransitionButtonProviderExtension
    {
        return new TransitionButtonProviderExtension(
            $this->workflowRegistry,
            $this->routeProvider,
            $this->originalUrlProvider
        );
    }
}
