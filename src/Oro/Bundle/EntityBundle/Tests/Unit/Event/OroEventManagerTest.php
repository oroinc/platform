<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\OroEventManager;
use Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub\StubEventListener;

class OroEventManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var OroEventManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->manager   = new OroEventManager($this->container);
    }

    public function testDisableAndReset()
    {
        $this->assertFalse($this->manager->hasDisabledListeners());
        $this->manager->disableListeners();
        $this->assertTrue($this->manager->hasDisabledListeners());
        $this->manager->clearDisabledListeners();
        $this->assertFalse($this->manager->hasDisabledListeners());
    }

    /**
     * @param bool $isEnabled
     * @dataProvider dispatchEventDataProvider
     */
    public function testDispatchEvent($isEnabled)
    {
        $eventName = 'postFlush';

        $affectedListener = new StubEventListener();
        $this->assertFalse($affectedListener->isFlushed);

        $notAffectedListener = $this->getMock('Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub\StubEventListener');
        $notAffectedListener->expects($this->once())->method($eventName);

        $listenerService = 'test.listener.service';
        $this->container->expects($this->once())->method('get')->with($listenerService)
            ->will($this->returnValue($affectedListener));

        $this->manager->addEventListener(array($eventName), $listenerService);     // class name Oro\Bundle\*
        $this->manager->addEventListener(array($eventName), $notAffectedListener); // class name Mock_*

        if (!$isEnabled) {
            $this->manager->disableListeners('^Oro');
        }
        $this->manager->dispatchEvent($eventName);

        $this->assertEquals($isEnabled, $affectedListener->isFlushed);
    }

    /**
     * @return array
     */
    public function dispatchEventDataProvider()
    {
        return array(
            'enabled'  => array(true),
            'disabled' => array(false),
        );
    }
}
