<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\OroEventManager;

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

    public function testEnableDisable()
    {
        $this->assertTrue($this->manager->isEnabled());
        $this->manager->disable();
        $this->assertFalse($this->manager->isEnabled());
        $this->manager->enable();
        $this->assertTrue($this->manager->isEnabled());
    }

    /**
     * @param bool $isEnabled
     * @dataProvider dispatchEventDataProvider
     */
    public function testDispatchEvent($isEnabled)
    {
        $eventName = 'postFlush';
        $listener = $this->getMock('Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub\StubEventListener');
        $listener->expects($isEnabled ? $this->once() : $this->never())->method($eventName);

        $isEnabled ? $this->manager->enable() : $this->manager->disable();
        $this->manager->addEventListener(array($eventName), $listener);
        $this->manager->dispatchEvent($eventName);
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
