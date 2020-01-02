<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\DbalTransportFactory;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\NullTransportFactory;
use Oro\Bundle\MessageQueueBundle\OroMessageQueueBundle;
use Oro\Component\MessageQueue\Job\Topics;
use Symfony\Component\DependencyInjection\Compiler\RegisterEnvVarProcessorsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroMessageQueueBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $addTopicPass = Compiler\AddTopicMetaPass::create()
            ->add(Topics::CALCULATE_ROOT_JOB_STATUS, 'Calculate root job status')
            ->add(Topics::ROOT_JOB_STOPPED, 'Root job stopped');

        $bundle = new OroMessageQueueBundle();
        /** @var OroMessageQueueExtension|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(OroMessageQueueExtension::class);
        $extension->expects($this->once())
            ->method('getAlias')
            ->willReturn('oro_message_queue');
        $extension->expects($this->exactly(2))
            ->method('addTransportFactory')
            ->withConsecutive(
                [new NullTransportFactory()],
                [new DbalTransportFactory()]
            );

        $container = new ContainerBuilder();
        $container->registerExtension($extension);
        $bundle->build($container);

        $this->assertArraySubset([
            new ResolveClassPass(),
            new ResolveInstanceofConditionalsPass(),
            new RegisterEnvVarProcessorsPass(),
            new Compiler\ConfigureDbalTransportExtensionsPass(),
            new Compiler\BuildExtensionsPass(),
            new Compiler\BuildRouteRegistryPass(),
            new Compiler\BuildMessageProcessorRegistryPass(),
            new Compiler\BuildTopicMetaSubscribersPass(),
            new Compiler\BuildDestinationMetaRegistryPass(),
            new Compiler\BuildMonologHandlersPass(),
            new Compiler\ConfigureClearersPass(),
            new Compiler\MakeLoggerServicesPersistentPass(),
            new Compiler\MakeAnnotationReaderServicesPersistentPass(),
            new Compiler\ProcessorLocatorPass(),
            $addTopicPass,
        ], $container->getCompilerPassConfig()->getBeforeOptimizationPasses());
    }
}
