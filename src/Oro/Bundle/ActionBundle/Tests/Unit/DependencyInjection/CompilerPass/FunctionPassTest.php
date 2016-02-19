<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\FunctionPass;

class FunctionPassTest extends AbstractPassTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->compilerPass = new FunctionPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return FunctionPass::FUNCTION_FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return FunctionPass::FUNCTION_TAG;
    }

    /**
     * {@inheritdoc}
     */
    protected function createServiceDefinition()
    {
        $definition = parent::createServiceDefinition();

        $definition->expects($this->once())
            ->method('getClass')
            ->willReturn('Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action\Stub\DispatcherAwareAction');
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('setDispatcher');

        return $definition;
    }
}
