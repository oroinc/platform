<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Manager;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestListener;
use Symfony\Component\DependencyInjection\Container;

class OptionalListenerManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $testListeners;

    /** @var Container|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var OptionalListenerManager */
    private $manager;

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

    public function testGetListeners()
    {
        $this->assertEquals($this->testListeners, $this->manager->getListeners());
    }

    public function testDisableListener()
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

    public function testDisableNonExistsListener()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Listener "test.bad_listener" does not exist or not optional');

        $this->manager->disableListener('test.bad_listener');
    }

    public function testDisableOneListener()
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
