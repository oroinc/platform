<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\NotificationHandlerPass;
use Oro\Component\DependencyInjection\Tests\Unit\AbstractExtensionCompilerPassTest;

class NotificationHandlerPassTest extends AbstractExtensionCompilerPassTest
{
    public function testProcess()
    {
        $this->assertServiceDefinitionMethodCalled('addHandler');
        $this->assertContainerBuilderCalled();

        $this->getCompilerPass()->process($this->containerBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCompilerPass()
    {
        return new NotificationHandlerPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return NotificationHandlerPass::SERVICE_KEY;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTagName()
    {
        return NotificationHandlerPass::TAG;
    }
}
