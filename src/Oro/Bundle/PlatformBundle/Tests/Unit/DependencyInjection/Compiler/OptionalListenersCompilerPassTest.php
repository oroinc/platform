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
        $nonInterfaceListener = new NonInterfaceListener();
        $nonInterfaceListenerDefinition = new Definition(get_class($nonInterfaceListener));
        $nonInterfaceListenerDefinition->addTag('doctrine.event_listener');
        $nonInterfaceSubscriber = new NonInterfaceListener();
        $nonInterfaceSubscriberDefinition = new Definition(get_class($nonInterfaceSubscriber));
        $nonInterfaceSubscriberDefinition->addTag('doctrine.event_subscriber');

        $testListener = new TestListener();
        $testListenerDefinition = new Definition(get_class($testListener));
        $testListenerDefinition->addTag('doctrine.event_listener');
        $testSubscriber = new TestListener();
        $testSubscriberDefinition = new Definition(get_class($testSubscriber));
        $testSubscriberDefinition->addTag('doctrine.event_subscriber');

        $container->addDefinitions(
            [
                'test.non_interface_listener'   => $nonInterfaceListenerDefinition,
                'test.non_interface_subscriber' => $nonInterfaceSubscriberDefinition,
                'test.listener'                 => $testListenerDefinition,
                'test.subscriber'               => $testSubscriberDefinition,
            ]
        );

        $managerDefinition = new Definition();
        $managerDefinition->addArgument([]);
        $container->addDefinitions([OptionalListenersCompilerPass::OPTIONAL_LISTENER_MANAGER => $managerDefinition]);
        $compiler = new OptionalListenersCompilerPass();
        $compiler->process($container);
        $this->assertEquals(['test.listener', 'test.subscriber'], $managerDefinition->getArgument(0));
    }
}
