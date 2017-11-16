<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\AttributeTypePass;
use Oro\Component\DependencyInjection\Tests\Unit\AbstractExtensionCompilerPassTest;

class AttributeTypePassTest extends AbstractExtensionCompilerPassTest
{
    public function testProcess()
    {
        $this->assertServiceDefinitionMethodCalled('addAttributeType');
        $this->assertContainerBuilderCalled();

        $this->getCompilerPass()->process($this->containerBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCompilerPass()
    {
        return new AttributeTypePass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return AttributeTypePass::SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTagName()
    {
        return AttributeTypePass::TAG;
    }
}
