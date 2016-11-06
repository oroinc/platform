<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowDefinitionBuilderExtensionCompilerPass;

use Oro\Component\DependencyInjection\Tests\Unit\AbstractExtensionCompilerPassTest;

class WorkflowDefinitionBuilderExtensionCompilerPassTest extends AbstractExtensionCompilerPassTest
{
    public function testProcess()
    {
        $this->assertServiceDefinitionMethodCalled('addExtension');
        $this->assertConteinerBuilderCalled();

        $this->getCompilerPass()->process($this->containerBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCompilerPass()
    {
        return new WorkflowDefinitionBuilderExtensionCompilerPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return WorkflowDefinitionBuilderExtensionCompilerPass::WORKFLOW_DEFINITION_BUILDER_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTagName()
    {
        return WorkflowDefinitionBuilderExtensionCompilerPass::WORKFLOW_DEFINITION_BUILDER_EXTENSION_TAG_NAME;
    }
}
