<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\DashboardBundle\DependencyInjection\Compiler\ValueConvertersPass;
use Symfony\Component\DependencyInjection\Definition;

class ValueConvertersPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ValueConvertersPass */
    protected $compiler;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    public function setUp()
    {
        $this->compiler = new ValueConvertersPass();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->getMock();
    }

    public function testProcessNotRegisterProvider()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_dashboard.widget_config_value.provider')
            ->willReturn(false);

        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compiler->process($this->container);
    }

    public function testProcess()
    {
        $definition = new Definition();

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_dashboard.widget_config_value.provider')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_dashboard.widget_config_value.provider')
            ->willReturn($definition);

        $convertersIds = [
            'converter1' => [['form_type' => 'form_type_1']],
            'converter2' => [['form_type' => 'form_type_2']],
        ];

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('oro_dashboard.value.converter')
            ->willReturn($convertersIds);

        $this->compiler->process($this->container);

        $calls = $definition->getMethodCalls();

        $i = 0;
        foreach ($convertersIds as $id => $attributes) {
            $this->assertEquals('addConverter', $calls[$i][0]);
            $this->assertEquals($id, (string)$calls[$i][1][1]);
            $this->assertEquals($attributes[0]['form_type'], $calls[$i][1][0]);
            $i++;
        }
    }
}
