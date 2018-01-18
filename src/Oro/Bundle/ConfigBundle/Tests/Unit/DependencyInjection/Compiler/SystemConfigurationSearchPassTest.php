<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationSearchPass;
use Oro\Component\DependencyInjection\Tests\Unit\AbstractExtensionCompilerPassTest;

class SystemConfigurationSearchPassTest extends AbstractExtensionCompilerPassTest
{
    public function testProcess()
    {
        $this->assertServiceDefinitionMethodCalled('addProvider');
        $this->assertContainerBuilderCalled();

        $this->getCompilerPass()->process($this->containerBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCompilerPass()
    {
        return new SystemConfigurationSearchPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return SystemConfigurationSearchPass::SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTagName()
    {
        return SystemConfigurationSearchPass::EXTENSION_TAG;
    }
}
