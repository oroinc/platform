<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\OptionalListenersCompilerPass;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\NonInterfaceListener;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OptionalListenersCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $nonInterfaceOrmListenerDefinition = new Definition(NonInterfaceListener::class);
        $nonInterfaceOrmListenerDefinition->addTag('doctrine.orm.entity_listener');
        $nonInterfaceListenerDefinition = new Definition(NonInterfaceListener::class);
        $nonInterfaceListenerDefinition->addTag('doctrine.event_listener');
        $nonInterfaceSubscriberDefinition = new Definition(NonInterfaceListener::class);
        $nonInterfaceSubscriberDefinition->addTag('doctrine.event_subscriber');

        $testOrmListenerDefinition = new Definition(TestListener::class);
        $testOrmListenerDefinition->addTag('doctrine.orm.entity_listener')->setPublic(false);
        $testListenerDefinition = new Definition(TestListener::class);
        $testListenerDefinition->addTag('doctrine.event_listener')->setPublic(false);
        $testSubscriberDefinition = new Definition(TestListener::class);
        $testSubscriberDefinition->addTag('doctrine.event_subscriber')->setPublic(false);

        $kernelListenerDefinition = new Definition(TestListener::class);
        $kernelListenerDefinition->addTag('kernel.event_listener')->setPublic(false);
        $kernelSubscriberDefinition = new Definition(TestListener::class);
        $kernelSubscriberDefinition->addTag('kernel.event_subscriber')->setPublic(false);

        $container->addDefinitions(
            [
                'test.non_orm_interface_listener' => $nonInterfaceOrmListenerDefinition,
                'test.non_interface_listener'     => $nonInterfaceListenerDefinition,
                'test.non_interface_subscriber'   => $nonInterfaceSubscriberDefinition,
                'test.orm_listener'               => $testOrmListenerDefinition,
                'test.listener'                   => $testListenerDefinition,
                'test.subscriber'                 => $testSubscriberDefinition,
                'kernel.listener'                 => $kernelListenerDefinition,
                'kernel.subscriber'               => $kernelSubscriberDefinition,
            ]
        );

        $this->assertFalse($kernelListenerDefinition->isPublic());
        $this->assertFalse($kernelSubscriberDefinition->isPublic());
        $this->assertFalse($testOrmListenerDefinition->isPublic());
        $this->assertFalse($testListenerDefinition->isPublic());
        $this->assertFalse($testSubscriberDefinition->isPublic());

        $managerDefinition = new Definition();
        $managerDefinition->addArgument([]);
        $container->addDefinitions([OptionalListenersCompilerPass::OPTIONAL_LISTENER_MANAGER => $managerDefinition]);
        $compiler = new OptionalListenersCompilerPass();
        $compiler->process($container);
        $this->assertEquals(
            [
                'kernel.listener',
                'kernel.subscriber',
                'test.orm_listener',
                'test.listener',
                'test.subscriber',
            ],
            $managerDefinition->getArgument(0)
        );

        $this->assertTrue($kernelListenerDefinition->isPublic());
        $this->assertTrue($kernelSubscriberDefinition->isPublic());
        $this->assertTrue($testOrmListenerDefinition->isPublic());
        $this->assertTrue($testListenerDefinition->isPublic());
        $this->assertTrue($testSubscriberDefinition->isPublic());
    }
}
