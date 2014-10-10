<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Manager;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestListener;

class OptionalListenerManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OptionalListenerManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var array
     */
    protected $testListeners;

    public function setUp()
    {
        $this->testListeners = [
            'test.listener1',
            'test.listener2',
            'test.listener3',
        ];
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new OptionalListenerManager($this->testListeners, $this->container);
    }

    public function testGetListeners()
    {
        $this->assertEquals($this->testListeners, $this->manager->getListeners());
    }

    public function testDisableListener()
    {
        $testListener = new TestListener();
        $testListener->enabled = false;
        $listenerId = 'test.listener2';
        $this->container->expects($this->once())
            ->method('get')
            ->with($listenerId)
            ->will($this->returnValue($testListener));
        $this->manager->disableListener($listenerId);
        $this->assertFalse($testListener->enabled);
    }

    public function testDisableNonExistsListener()
    {
        $listenerId = 'test.bad_listener';
        $this->setExpectedException(
            '\InvalidArgumentException',
            sprintf(
                'Optional listener "&s" does not exists',
                $listenerId
            )
        );
        $this->manager->disableListener($listenerId);
    }

    public function testDisableAllListeners()
    {
        $listener1 = new TestListener();
        $listener2 = new TestListener();
        $listener3 = new TestListener();
        $listener1->enabled = false;
        $listener2->enabled = false;
        $listener3->enabled = false;
        $listeners = [
            'test.listener1' => $listener1,
            'test.listener2' => $listener2,
            'test.listener3' => $listener3,
        ];


        $this->container->expects($this->exactly(3))
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($listenerName) use ($listeners) {
                        return $listeners[$listenerName];
                    }
                )
            );
        $this->manager->disableListeners();
        $this->assertFalse($listener1->enabled);
        $this->assertFalse($listener2->enabled);
        $this->assertFalse($listener3->enabled);
    }

    public function testDisableOneListener()
    {
        $testListener = new TestListener();
        $testListener->enabled = false;
        $listenerId = 'test.listener3';
        $this->container->expects($this->once())
            ->method('get')
            ->with($listenerId)
            ->will($this->returnValue($testListener));
        $this->manager->disableListeners([$listenerId]);
        $this->assertFalse($testListener->enabled);
    }
}
