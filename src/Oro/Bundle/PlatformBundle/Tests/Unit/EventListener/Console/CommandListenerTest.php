<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\Console;

use Oro\Bundle\PlatformBundle\EventListener\Console\CommandListener;
use Oro\Bundle\PlatformBundle\Maintenance\Events;

class CommandListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var CommandListener
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcherInterface;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $command;

    protected function setUp()
    {
        $this->dispatcherInterface = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->getMockForAbstractClass();

        $this->event = $this->getMockBuilder('Symfony\Component\Console\Event\ConsoleTerminateEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = $this->getMockBuilder('Symfony\Component\Console\Command\Command')
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new CommandListener($this->dispatcherInterface);
    }

    public function testAfterExecuteShouldDispatchMaintenanceOnEvent()
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(CommandListener::LEXIK_MAINTENANCE_LOCK));

        $this->dispatcherInterface->expects($this->once())->method('dispatch')->with(Events::MAINTENANCE_ON);

        $this->event->expects($this->once())->method('getCommand')->will($this->returnValue($this->command));

        $this->target->afterExecute($this->event);
    }

    public function testAfterExecuteShouldDispatchMaintenanceOffEvent()
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(CommandListener::LEXIK_MAINTENANCE_UNLOCK));

        $this->dispatcherInterface->expects($this->once())->method('dispatch')->with(Events::MAINTENANCE_OFF);

        $this->event->expects($this->once())->method('getCommand')->will($this->returnValue($this->command));

        $this->target->afterExecute($this->event);
    }
}
