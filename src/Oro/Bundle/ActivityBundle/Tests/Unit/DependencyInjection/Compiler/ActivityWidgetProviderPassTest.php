<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ActivityBundle\DependencyInjection\Compiler\ActivityWidgetProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ActivityWidgetProviderPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessNoProviderDefinition()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $compiler  = new ActivityWidgetProviderPass();

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(ActivityWidgetProviderPass::SERVICE_ID)
            ->will($this->returnValue(false));
        $container->expects($this->never())
            ->method('findTaggedServiceIds');

        $compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $compiler  = new ActivityWidgetProviderPass();

        $chainProvider = new Definition();
        $provider1     = new Definition();
        $provider2     = new Definition();
        $provider3     = new Definition();
        $provider4     = new Definition();

        $provider1->addTag(ActivityWidgetProviderPass::TAG_NAME, ['priority' => 100]);
        $provider2->addTag(ActivityWidgetProviderPass::TAG_NAME, ['priority' => -100]);
        $provider3->addTag(ActivityWidgetProviderPass::TAG_NAME);
        $provider4->addTag(ActivityWidgetProviderPass::TAG_NAME, ['priority' => 100]);

        $container->addDefinitions(
            [
                ActivityWidgetProviderPass::SERVICE_ID => $chainProvider,
                'provider1'                            => $provider1,
                'provider2'                            => $provider2,
                'provider3'                            => $provider3,
                'provider4'                            => $provider4,
            ]
        );

        $compiler->process($container);

        $this->assertEquals(
            [
                ['addProvider', [new Reference('provider1')]],
                ['addProvider', [new Reference('provider4')]],
                ['addProvider', [new Reference('provider3')]],
                ['addProvider', [new Reference('provider2')]],
            ],
            $chainProvider->getMethodCalls()
        );
    }
}
