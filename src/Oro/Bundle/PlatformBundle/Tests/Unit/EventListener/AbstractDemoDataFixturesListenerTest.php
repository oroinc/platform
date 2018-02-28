<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;

class AbstractDemoDataFixturesListenerTest extends AbstractDataFixturesListenerTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->listener = new AbstractDemoDataFixturesListener($this->listenerManager);
        $this->listener->disableListener(self::LISTENERS[0]);
        $this->listener->disableListener(self::LISTENERS[1]);
    }

    /**
     * @param bool $isDemoData
     */
    protected function assertListenerManagerCalled(bool $isDemoData)
    {
        if ($isDemoData) {
            $this->listenerManager->expects($this->at(0))
                ->method('disableListeners')
                ->with(self::LISTENERS);
            $this->listenerManager->expects($this->at(1))
                ->method('enableListeners')
                ->with(self::LISTENERS);
        } else {
            $this->listenerManager->expects($this->never())
                ->method($this->anything());
        }
    }
}
