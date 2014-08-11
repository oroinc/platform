<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailTemplateVariablesPass;
use Symfony\Component\DependencyInjection\Reference;

class EmailTemplateVariablesPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessNoServices()
    {
        $containerBuilder = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(EmailTemplateVariablesPass::SERVICE_KEY))
            ->will($this->returnValue(false));
        $containerBuilder->expects($this->never())
            ->method('getDefinition');
        $containerBuilder->expects($this->never())
            ->method('findTaggedServiceIds');

        $pass = new EmailTemplateVariablesPass();
        $pass->process($containerBuilder);
    }

    public function testProcess()
    {
        $service = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(EmailTemplateVariablesPass::SERVICE_KEY)
            ->will($this->returnValue(true));
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(EmailTemplateVariablesPass::SERVICE_KEY)
            ->will($this->returnValue($service));
        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->will(
                $this->returnValue(
                    [
                        'provider2' => [['scope' => 'system', 'priority' => 1]],
                        'provider1' => [['scope' => 'system', 'priority' => 3]],
                        'provider3' => [['scope' => 'entity']],
                    ]
                )
            );

        $service->expects($this->at(0))
            ->method('addMethodCall')
            ->with(
                'addSystemVariablesProvider',
                [new Reference('provider1')]
            );
        $service->expects($this->at(1))
            ->method('addMethodCall')
            ->with(
                'addSystemVariablesProvider',
                [new Reference('provider2')]
            );
        $service->expects($this->at(2))
            ->method('addMethodCall')
            ->with(
                'addEntityVariablesProvider',
                [new Reference('provider3')]
            );

        $pass = new EmailTemplateVariablesPass();
        $pass->process($containerBuilder);
    }
}
