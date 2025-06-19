<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

class SwitchableDependencyInjectionExtensionTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private FormExtensionInterface&MockObject $extension1;
    private FormExtensionInterface&MockObject $extension2;
    private SwitchableDependencyInjectionExtension $switchableExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->extension1 = $this->createMock(FormExtensionInterface::class);
        $this->extension2 = $this->createMock(FormExtensionInterface::class);

        $this->switchableExtension = new SwitchableDependencyInjectionExtension($this->container);
        $this->switchableExtension->addExtension('extension1', 'extension1_service');
        $this->switchableExtension->addExtension('extension2', 'extension2_service');
    }

    public function testDefaultExtension(): void
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

    public function testSwitchFormExtension(): void
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

    public function testHasType(): void
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

    public function testGetType(): void
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

    public function testHasTypeExtensions(): void
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

    public function testGetTypeExtensions(): void
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

    public function testGetTypeGuesser(): void
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
