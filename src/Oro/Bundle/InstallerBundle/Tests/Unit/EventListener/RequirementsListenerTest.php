<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\EventListener;

use Oro\Bundle\InstallerBundle\EventListener\RequirementsListener;

class RequirementsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequirementsListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    protected function setUp()
    {
        $this->helper = $this
            ->getMockBuilder('Oro\Bundle\InstallerBundle\Helper\RequirementsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this
            ->getMockBuilder('Symfony\Component\Console\Event\ConsoleCommandEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RequirementsListener($this->helper);
    }

    public function testInstallCommand()
    {
        $command = $this->getMock('Oro\Bundle\InstallerBundle\Command\InstallCommandInterface');

        $this->event
            ->expects($this->once())
            ->method('getCommand')
            ->will($this->returnValue($command));

        $this->helper
            ->expects($this->never())
            ->method('getNotFulfilledRequirements');

        $this->listener->onConsoleCommand($this->event);
    }

    public function testCommandWithFulfilledRequirements()
    {
        $command = $this
            ->getMockBuilder('Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event
            ->expects($this->once())
            ->method('getCommand')
            ->will($this->returnValue($command));

        $this->helper
            ->expects($this->once())
            ->method('getNotFulfilledRequirements')
            ->will($this->returnValue([]));

        $this->listener->onConsoleCommand($this->event);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not all requirements were fulfilled
     */
    public function testCommandWithNonFulfilledRequirements()
    {
        $command = $this
            ->getMockBuilder('Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event
            ->expects($this->once())
            ->method('getCommand')
            ->will($this->returnValue($command));

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $this->event
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue($output));

        $this->helper
            ->expects($this->once())
            ->method('getNotFulfilledRequirements')
            ->will($this->returnValue([new RequirementStub('Requirement')]));

        $this->listener->onConsoleCommand($this->event);
    }
}
