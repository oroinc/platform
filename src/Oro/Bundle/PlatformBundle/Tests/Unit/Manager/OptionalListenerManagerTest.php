<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Manager;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class OptionalListenerManagerTest extends TestCase
{
    private array $testListeners;
    private Container&MockObject $container;
    private OptionalListenerManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->testListeners = [
            'test.listener1',
            'test.listener2',
            'test.listener3',
        ];
        $this->container = $this->createMock(Container::class);

        $this->manager = new OptionalListenerManager($this->testListeners, $this->container);
    }

    public function testGetListeners(): void
    {
        $this->assertEquals($this->testListeners, $this->manager->getListeners());
    }

    public function testDisableListener(): void
    {
        $testListener = new TestListener();
        $testListener->resetEnabled();
        $listenerId = 'test.listener2';
        $this->container->expects($this->once())
            ->method('get')
            ->with($listenerId)
            ->willReturn($testListener);
        $this->manager->disableListener($listenerId);
        $this->assertFalse($testListener->getEnabled());
    }

    public function testDisableNonExistsListener(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Listener "test.bad_listener" does not exist or not optional');

        $this->manager->disableListener('test.bad_listener');
    }

    public function testDisableOneListener(): void
    {
        $testListener = new TestListener();
        $testListener->resetEnabled();
        $listenerId = 'test.listener3';
        $this->container->expects($this->once())
            ->method('get')
            ->with($listenerId)
            ->willReturn($testListener);
        $this->manager->disableListeners([$listenerId]);
        $this->assertFalse($testListener->getEnabled());
    }
}
