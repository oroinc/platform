<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\Console;

use Oro\Bundle\PlatformBundle\EventListener\Console\OptionalListenersListener;
use Oro\Bundle\PlatformBundle\EventListener\Console\OptionalListenersOptionsListener;

class OptionalListenersOptionsListenerTest extends AddGlobalOptionsListenerTestCase
{
    /**
     * @var OptionalListenersOptionsListener
     */
    private $listener;

    protected function setUp()
    {
        $this->listener = new OptionalListenersOptionsListener();
    }

    public function testOnConsoleCommand()
    {
        $event = $this->getEvent();
        $this->listener->onConsoleCommand($event);

        $this->assertEquals(
            [OptionalListenersListener::DISABLE_OPTIONAL_LISTENERS],
            array_keys($event->getCommand()->getApplication()->getDefinition()->getOptions())
        );
        $this->assertEquals(
            [OptionalListenersListener::DISABLE_OPTIONAL_LISTENERS],
            array_keys($event->getCommand()->getDefinition()->getOptions())
        );
    }
}
