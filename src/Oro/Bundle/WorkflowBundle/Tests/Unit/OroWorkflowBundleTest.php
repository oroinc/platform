<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;
use Oro\Bundle\WorkflowBundle\OroWorkflowBundle;
use Oro\Component\ChainProcessor\DependencyInjection\LoadAndBuildProcessorsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

        $containerBuilder->expects($this->at(6))
            ->method('addCompilerPass')
            ->with(
                new LoadAndBuildProcessorsCompilerPass(
                    'oro_workflow.processor_bag_config_provider',
                    'oro_workflow.processor'
                )
            );

        $containerBuilder->expects($this->at(7))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\EventsCompilerPass::class
                )
            );

        $addTopicMetaPass = AddTopicMetaPass::create();
        $addTopicMetaPass->add(Topics::EXECUTE_PROCESS_JOB);

        $containerBuilder->expects($this->at(8))->method('addCompilerPass')->with($addTopicMetaPass);

        $bundle = new OroWorkflowBundle();
        $bundle->build($containerBuilder);
    }
}
