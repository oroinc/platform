<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ActionPass;

class ActionPassTest extends AbstractPassTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->compilerPass = new ActionPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return ActionPass::ACTION_FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return ActionPass::ACTION_TAG;
    }

    /**
     * {@inheritdoc}
     */
    protected function createServiceDefinition()
    {
        $definition = parent::createServiceDefinition();

        $definition->expects($this->once())
            ->method('getClass')
            ->willReturn('Oro\Component\Action\Tests\Unit\Action\Stub\DispatcherAwareAction');
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('setDispatcher');

        return $definition;
    }
}
