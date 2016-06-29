<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension;

class SwitchableDependencyInjectionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extension1;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extension2;

    /** @var SwitchableDependencyInjectionExtension */
    protected $switchableExtension;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->extension1 = $this->getMock('Symfony\Component\Form\FormExtensionInterface');
        $this->extension2 = $this->getMock('Symfony\Component\Form\FormExtensionInterface');

        $this->switchableExtension = new SwitchableDependencyInjectionExtension($this->container);
        $this->switchableExtension->addExtension('extension1', 'extension1_service');
        $this->switchableExtension->addExtension('extension2', 'extension2_service');
    }

    public function testDefaultExtension()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects($this->once())
            ->method('hasType')
            ->willReturn(true);

        $this->assertTrue($this->switchableExtension->hasType('type1'));
    }

    public function testSwitchFormExtension()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('extension2_service')
            ->willReturn($this->extension2);
        $this->extension2->expects($this->once())
            ->method('hasType')
            ->willReturn(true);

        $this->switchableExtension->switchFormExtension('extension2');
        $this->assertTrue($this->switchableExtension->hasType('type1'));
    }

    public function testHasType()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects($this->once())
            ->method('hasType')
            ->with('type1')
            ->willReturn(true);

        $this->assertTrue($this->switchableExtension->hasType('type1'));
    }

    public function testGetType()
    {
        $type = $this->getMock('Symfony\Component\Form\FormTypeInterface');

        $this->container->expects($this->once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects($this->once())
            ->method('getType')
            ->with('type1')
            ->willReturn($type);

        $this->assertSame($type, $this->switchableExtension->getType('type1'));
    }

    public function testHasTypeExtensions()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects($this->once())
            ->method('hasTypeExtensions')
            ->with('type1')
            ->willReturn(true);

        $this->assertTrue($this->switchableExtension->hasTypeExtensions('type1'));
    }

    public function testGetTypeExtensions()
    {
        $typeExtensions = [
            $this->getMock('Symfony\Component\Form\FormTypeExtensionInterface')
        ];

        $this->container->expects($this->once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects($this->once())
            ->method('getTypeExtensions')
            ->with('type1')
            ->willReturn($typeExtensions);

        $this->assertSame($typeExtensions, $this->switchableExtension->getTypeExtensions('type1'));
    }

    public function testGetTypeGuesser()
    {
        $typeGuesser = $this->getMock('Symfony\Component\Form\FormTypeGuesserInterface');

        $this->container->expects($this->once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects($this->once())
            ->method('getTypeGuesser')
            ->willReturn($typeGuesser);

        $this->assertSame($typeGuesser, $this->switchableExtension->getTypeGuesser());
    }
}
