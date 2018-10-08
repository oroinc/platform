<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;
use Oro\Bundle\WorkflowBundle\OroWorkflowBundle;
use Oro\Component\ChainProcessor\DependencyInjection\LoadAndBuildProcessorsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class OroWorkflowBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addCompilerPass'])
            ->getMock();

        $addTopicMetaPass = AddTopicMetaPass::create();
        $addTopicMetaPass->add(Topics::EXECUTE_PROCESS_JOB);

        $expectations = [
            $this->isInstanceOf(Compiler\AddAttributeNormalizerCompilerPass::class),
            $this->isInstanceOf(Compiler\AddWorkflowValidationLoaderCompilerPass::class),
            new RegisterListenersPass(
                'oro_workflow.changes.event.dispatcher',
                'oro_workflow.changes.listener',
                'oro_workflow.changes.subscriber'
            ),
            $this->isInstanceOf(Compiler\EventTriggerExtensionCompilerPass::class),
            $this->isInstanceOf(Compiler\WorkflowConfigurationHandlerCompilerPass::class),
            $this->isInstanceOf(Compiler\WorkflowDefinitionBuilderExtensionCompilerPass::class),
            new LoadAndBuildProcessorsCompilerPass(
                'oro_workflow.processor_bag_config_provider',
                'oro_workflow.processor'
            ),
            $this->isInstanceOf(Compiler\EventsCompilerPass::class),
            $addTopicMetaPass,
        ];

        foreach ($expectations as $key => $expectation) {
            $containerBuilder->expects($this->at($key))
                ->method('addCompilerPass')
                ->with($expectation);
        }

        $bundle = new OroWorkflowBundle();
        $bundle->build($containerBuilder);
    }
}
