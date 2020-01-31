<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\OroEventManager;
use Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub\StubEventListener;

class OroEventManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    /**
     * @var OroEventManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
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

        $notAffectedListener = $this->createMock('Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub\StubEventListener');
        $notAffectedListener->expects($this->once())->method($eventName);

        // https://github.com/symfony/symfony/pull/31335#issuecomment-562576326
        $this->manager->addEventListener([$eventName], $affectedListener);     // class name Oro\Bundle\*
        $this->manager->addEventListener([$eventName], $notAffectedListener); // class name Mock_*

        if (!$isEnabled) {
            $this->manager->disableListeners('^Oro');
        }
        $this->manager->dispatchEvent($eventName);

        $this->assertEquals($isEnabled, $affectedListener->isFlushed);
    }

    /**
     * @return array
     */
    public function dispatchEventDataProvider(): array
    {
        return [
            'enabled'  => [true],
            'disabled' => [false],
        ];
    }
}
