<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowConfigurationHandlerCompilerPass;

class WorkflowConfigurationHandlerCompilerPassTest extends AbstractExtensionCompilerPassTest
{
    public function testProcess()
    {
        $this->assertServiceDefinitionMethodCalled('addHandler');
        $this->assertConteinerBuilderCalled();

        $this->getCompilerPass()->process($this->containerBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCompilerPass()
    {
        return new WorkflowConfigurationHandlerCompilerPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return WorkflowConfigurationHandlerCompilerPass::DEFINITION_HANDLE_BUILDER_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTagName()
    {
        return WorkflowConfigurationHandlerCompilerPass::WORKFLOW_CONFIGURATION_HANDLER_TAG_NAME;
    }
}
