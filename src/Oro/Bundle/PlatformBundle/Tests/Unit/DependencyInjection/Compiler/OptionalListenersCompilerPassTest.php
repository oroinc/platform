<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\OptionalListenersCompilerPass;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\NonInterfaceListener;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OptionalListenersCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $nonInterfaceListenerDefinition = new Definition(NonInterfaceListener::class);
        $nonInterfaceListenerDefinition->addTag('doctrine.event_listener');
        $nonInterfaceSubscriberDefinition = new Definition(NonInterfaceListener::class);
        $nonInterfaceSubscriberDefinition->addTag('doctrine.event_subscriber');

        $testListenerDefinition = new Definition(TestListener::class);
        $testListenerDefinition->addTag('doctrine.event_listener');
        $testSubscriberDefinition = new Definition(TestListener::class);
        $testSubscriberDefinition->addTag('doctrine.event_subscriber');

        $kernelListenerDefinition = new Definition(TestListener::class);
        $kernelListenerDefinition->addTag('kernel.event_listener');
        $kernelSubscriberDefinition = new Definition(TestListener::class);
        $kernelSubscriberDefinition->addTag('kernel.event_subscriber');

        $container->addDefinitions(
            [
                'test.non_interface_listener'   => $nonInterfaceListenerDefinition,
                'test.non_interface_subscriber' => $nonInterfaceSubscriberDefinition,
                'test.listener'                 => $testListenerDefinition,
                'test.subscriber'               => $testSubscriberDefinition,
                'kernel.listener'               => $kernelListenerDefinition,
                'kernel.subscriber'             => $kernelSubscriberDefinition,
            ]
        );

        $managerDefinition = new Definition();
        $managerDefinition->addArgument([]);
        $container->addDefinitions([OptionalListenersCompilerPass::OPTIONAL_LISTENER_MANAGER => $managerDefinition]);
        $compiler = new OptionalListenersCompilerPass();
        $compiler->process($container);
        $this->assertEquals(
            [
                'kernel.listener',
                'kernel.subscriber',
                'test.listener',
                'test.subscriber',
            ],
            $managerDefinition->getArgument(0)
        );
    }
}
