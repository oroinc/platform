<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\ActionProvidersPass;
use Oro\Component\DependencyInjection\Tests\Unit\AbstractExtensionCompilerPassTest;

class ActionProvidersPassTest extends AbstractExtensionCompilerPassTest
{
    public function testProcess()
    {
        $this->assertServiceDefinitionMethodCalled('addActionProvider');
        $this->assertContainerBuilderCalled();

        $this->getCompilerPass()->process($this->containerBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCompilerPass()
    {
        return new ActionProvidersPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return ActionProvidersPass::SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTagName()
    {
        return ActionProvidersPass::EXTENSION_TAG;
    }
}
