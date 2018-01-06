<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\Console;

use Oro\Bundle\SecurityBundle\EventListener\ConsoleContextListener;
use Oro\Bundle\SecurityBundle\EventListener\ConsoleContextOptionsListener;

class ConsoleContextOptionsListenerTest extends AddGlobalOptionsListenerTestCase
{
    /**
     * @var ConsoleContextOptionsListener
     */
    private $listener;

    protected function setUp()
    {
        $this->listener = new ConsoleContextOptionsListener();
    }

    public function testOnConsoleCommand()
    {
        $event = $this->getEvent();
        $this->listener->onConsoleCommand($event);

        $this->assertEquals(
            [
                ConsoleContextListener::OPTION_USER,
                ConsoleContextListener::OPTION_ORGANIZATION,
            ],
            array_keys($event->getCommand()->getApplication()->getDefinition()->getOptions())
        );
        $this->assertEquals(

            [
                ConsoleContextListener::OPTION_USER,
                ConsoleContextListener::OPTION_ORGANIZATION,
            ],
            array_keys($event->getCommand()->getDefinition()->getOptions())
        );
    }
}
