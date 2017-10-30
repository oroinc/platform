<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowDataUpdaterCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\AbstractExtensionCompilerPassTest;

class WorkflowDataUpdaterCompilerPassTest extends AbstractExtensionCompilerPassTest
{
    public function testProcess()
    {
        $this->assertServiceDefinitionMethodCalled('addUpdater');
        $this->assertContainerBuilderCalled();

        $this->getCompilerPass()->process($this->containerBuilder);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCompilerPass()
    {
        return new WorkflowDataUpdaterCompilerPass();
    }

    /**
     * {@inheritDoc}
     */
    protected function getServiceId()
    {
        return WorkflowDataUpdaterCompilerPass::SERVICE_ID;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTagName()
    {
        return WorkflowDataUpdaterCompilerPass::TAG;
    }
}
