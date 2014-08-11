<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\OroEventManager;
use Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub\StubEventListener;

class OroEventManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroEventManager
     */
    protected $manager;

    protected function setUp()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->manager = new OroEventManager($container);
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

        $this->manager->addEventListener(array($eventName), $affectedListener);    // class name Oro\Bundle\*
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
