<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;

use Oro\Bundle\SearchBundle\EventListener\UpdateSchemaDoctrineListener;

class UpdateSchemaListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $input;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $output;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineCommand;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $application;

    /**
     * @var UpdateSchemaDoctrineListener
     */
    protected $listener;

    public function setUp()
    {
        $this->eventMock = $this
            ->getMockBuilder('Symfony\Component\Console\Event\ConsoleTerminateEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $this->eventMock
            ->expects($this->any())
            ->method('getInput')
            ->will($this->returnValue($this->input));

        $this->eventMock
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue($this->output));

        $this->command = $this->getMock('Oro\Bundle\SearchBundle\Command\AddFulltextIndexesCommand', ['execute']);

        $this->doctrineCommand = $this->getMock(
            'Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand',
            ['execute', 'getApplication']
        );

        $this->application = $this->getMock('Symfony\Component\Console\Application');

        $this->listener = new UpdateSchemaDoctrineListener();
    }

    public function testNotRelatedCommand()
    {
        $command = $this->getMock('Oro\Bundle\SearchBundle\Command\IndexCommand', ['execute']);

        $this->eventMock
            ->expects($this->any())
            ->method('getCommand')
            ->will($this->returnValue($command));

        $this->application
            ->expects($this->never())
            ->method('run');

        $this->listener->onConsoleTerminate($this->eventMock);
    }

    public function testRelatedCommandWithoutOption()
    {
        $this->eventMock
            ->expects($this->any())
            ->method('getCommand')
            ->will($this->returnValue(new UpdateSchemaDoctrineCommand()));

        $this->application
            ->expects($this->never())
            ->method('run');

        $this->listener->onConsoleTerminate($this->eventMock);
    }

    public function testRelatedCommand()
    {
        $this->input
            ->expects($this->once())
            ->method('getOption')
            ->with('force')
            ->will($this->returnValue(true));

        $this->doctrineCommand
            ->expects($this->once())
            ->method('getApplication')
            ->will($this->returnValue($this->application));

        $this->eventMock
            ->expects($this->any())
            ->method('getCommand')
            ->will($this->returnValue($this->doctrineCommand));

        $this->application
            ->expects($this->once())
            ->method('run');

        $this->listener->onConsoleTerminate($this->eventMock);
    }
}
