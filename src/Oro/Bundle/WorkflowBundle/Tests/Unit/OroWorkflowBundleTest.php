<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler as Compiler;
use Oro\Bundle\WorkflowBundle\OroWorkflowBundle;

class OroWorkflowBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addCompilerPass'])
            ->getMock();

        $containerBuilder->expects($this->at(0))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\AddAttributeNormalizerCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(1))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\AddWorkflowValidationLoaderCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(2))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\WorkflowChangesEventsCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(3))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\EventTriggerExtensionCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(4))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\WorkflowConfigurationHandlerCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(5))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\WorkflowDefinitionBuilderExtensionCompilerPass::class
                )
            );

        $bundle = new OroWorkflowBundle();
        $bundle->build($containerBuilder);
    }
}
