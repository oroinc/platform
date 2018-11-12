<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

class SwitchableDependencyInjectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormExtensionInterface */
    private $extension1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormExtensionInterface */
    private $extension2;

    /** @var SwitchableDependencyInjectionExtension */
    private $switchableExtension;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->extension1 = $this->createMock(FormExtensionInterface::class);
        $this->extension2 = $this->createMock(FormExtensionInterface::class);

        $this->switchableExtension = new SwitchableDependencyInjectionExtension($this->container);
        $this->switchableExtension->addExtension('extension1', 'extension1_service');
        $this->switchableExtension->addExtension('extension2', 'extension2_service');
    }

    public function testDefaultExtension()
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects(self::once())
            ->method('hasType')
            ->willReturn(true);

        self::assertTrue($this->switchableExtension->hasType('type1'));
    }

    public function testSwitchFormExtension()
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('extension2_service')
            ->willReturn($this->extension2);
        $this->extension2->expects(self::once())
            ->method('hasType')
            ->willReturn(true);

        $this->switchableExtension->switchFormExtension('extension2');
        self::assertTrue($this->switchableExtension->hasType('type1'));
    }

    public function testHasType()
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects(self::once())
            ->method('hasType')
            ->with('type1')
            ->willReturn(true);

        self::assertTrue($this->switchableExtension->hasType('type1'));
    }

    public function testGetType()
    {
        $type = $this->createMock(FormTypeInterface::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects(self::once())
            ->method('getType')
            ->with('type1')
            ->willReturn($type);

        self::assertSame($type, $this->switchableExtension->getType('type1'));
    }

    public function testHasTypeExtensions()
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects(self::once())
            ->method('hasTypeExtensions')
            ->with('type1')
            ->willReturn(true);

        self::assertTrue($this->switchableExtension->hasTypeExtensions('type1'));
    }

    public function testGetTypeExtensions()
    {
        $typeExtensions = [
            $this->createMock(FormTypeExtensionInterface::class)
        ];

        $this->container->expects(self::once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects(self::once())
            ->method('getTypeExtensions')
            ->with('type1')
            ->willReturn($typeExtensions);

        self::assertSame($typeExtensions, $this->switchableExtension->getTypeExtensions('type1'));
    }

    public function testGetTypeGuesser()
    {
        $typeGuesser = $this->createMock(FormTypeGuesserInterface::class);

        $this->container->expects(self::once())
            ->method('get')
            ->with('extension1_service')
            ->willReturn($this->extension1);
        $this->extension1->expects(self::once())
            ->method('getTypeGuesser')
            ->willReturn($typeGuesser);

        self::assertSame($typeGuesser, $this->switchableExtension->getTypeGuesser());
    }
}
