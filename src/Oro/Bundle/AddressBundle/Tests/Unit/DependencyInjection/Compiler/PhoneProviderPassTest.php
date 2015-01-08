<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\AddressBundle\DependencyInjection\Compiler\PhoneProviderPass;
use Symfony\Component\DependencyInjection\Reference;

class PhoneProviderPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * Environment setup
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->getMock();
    }

    public function testProcessNotRegisterProvider()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('oro_address.provider.phone'))
            ->will($this->returnValue(false));

        $this->container->expects($this->never())
            ->method('getDefinition');
        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $compilerPass = new PhoneProviderPass();
        $compilerPass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Tag attribute "class" is required for "provider1" service
     */
    public function testProcessNoClass()
    {
        $serviceIds = array(
            'provider1' => array(array()),
        );

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('oro_address.provider.phone'))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo('oro_address.phone_provider'))
            ->will($this->returnValue($serviceIds));

        $this->container->expects($this->never())
            ->method('getDefinition')
            ->with($this->equalTo('oro_address.provider.phone'));

        $compilerPass = new PhoneProviderPass();
        $compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with(
                $this->equalTo('addPhoneProvider'),
                $this->equalTo(array('Test\Class1', new Reference('provider4')))
            );
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with(
                $this->equalTo('addPhoneProvider'),
                $this->equalTo(array('Test\Class1', new Reference('provider1')))
            );
        $definition->expects($this->at(2))
            ->method('addMethodCall')
            ->with(
                $this->equalTo('addPhoneProvider'),
                $this->equalTo(array('Test\Class2', new Reference('provider2')))
            );
        $definition->expects($this->at(3))
            ->method('addMethodCall')
            ->with(
                $this->equalTo('addPhoneProvider'),
                $this->equalTo(array('Test\Class1', new Reference('provider3')))
            );

        $serviceIds = array(
            'provider1' => array(array('class' => 'Test\Class1')),
            'provider2' => array(array('class' => 'Test\Class2')),
            'provider3' => array(array('class' => 'Test\Class1', 'priority' => 100)),
            'provider4' => array(array('class' => 'Test\Class1', 'priority' => -100)),
        );

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('oro_address.provider.phone'))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo('oro_address.provider.phone'))
            ->will($this->returnValue($definition));
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo('oro_address.phone_provider'))
            ->will($this->returnValue($serviceIds));

        $compilerPass = new PhoneProviderPass();
        $compilerPass->process($this->container);
    }
}
