<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\EventListener\UpdateSchemaDoctrineListener;

class UpdateSchemaListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchEngine;

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
     * @var UpdateSchemaDoctrineListener
     */
    protected $listener;

    public function setUp()
    {
        $this->eventMock = $this
            ->getMockBuilder('Symfony\Component\Console\Event\ConsoleTerminateEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->input  = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $this->eventMock
            ->expects($this->any())
            ->method('getInput')
            ->will($this->returnValue($this->input));

        $this->eventMock
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue($this->output));

        $this->doctrineCommand = $this
            ->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand')
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchEngine = $this
            ->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Orm')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new UpdateSchemaDoctrineListener($this->searchEngine);
    }

    public function testNotRelatedCommand()
    {
        $command = $this->getMock('Oro\Bundle\SearchBundle\Command\IndexCommand', ['execute']);

        $this->eventMock
            ->expects($this->any())
            ->method('getCommand')
            ->will($this->returnValue($command));

        $this->searchEngine
            ->expects($this->never())
            ->method('reindex');

        $this->listener->onConsoleTerminate($this->eventMock);
    }

    public function testRelatedCommandWithoutOption()
    {
        $this->input
            ->expects($this->once())
            ->method('getOption')
            ->with('force')
            ->will($this->returnValue(null));

        $this->eventMock
            ->expects($this->any())
            ->method('getCommand')
            ->will($this->returnValue($this->doctrineCommand));

        $this->searchEngine
            ->expects($this->never())
            ->method('reindex');

        $this->listener->onConsoleTerminate($this->eventMock);
    }

    public function testRelatedCommand()
    {
        $this->input
            ->expects($this->once())
            ->method('getOption')
            ->with('force')
            ->will($this->returnValue(true));

        $this->eventMock
            ->expects($this->any())
            ->method('getCommand')
            ->will($this->returnValue($this->doctrineCommand));

        $this->searchEngine
            ->expects($this->once())
            ->method('reindex');

        $this->listener->onConsoleTerminate($this->eventMock);
    }
}
