<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;
use Oro\Bundle\WorkflowBundle\OroWorkflowBundle;
use Oro\Component\ChainProcessor\DependencyInjection\LoadProcessorsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWorkflowBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addCompilerPass'])
            ->getMock();

        $addTopicMetaPass = AddTopicMetaPass::create();
        $addTopicMetaPass->add(Topics::EXECUTE_PROCESS_JOB);

        $expectations = [
            $this->isInstanceOf(Compiler\AddAttributeNormalizerCompilerPass::class),
            $this->isInstanceOf(Compiler\AddWorkflowValidationLoaderCompilerPass::class),
            $this->isInstanceOf(Compiler\WorkflowChangesEventsCompilerPass::class),
            $this->isInstanceOf(Compiler\EventTriggerExtensionCompilerPass::class),
            $this->isInstanceOf(Compiler\WorkflowConfigurationHandlerCompilerPass::class),
            $this->isInstanceOf(Compiler\WorkflowDefinitionBuilderExtensionCompilerPass::class),
            new LoadProcessorsCompilerPass(
                'oro_workflow.processor_bag',
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
