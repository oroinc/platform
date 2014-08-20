<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\EventListener\UpdateSchemaDoctrineListener;
use Oro\Bundle\SearchBundle\DependencyInjection\Configuration;

class UpdateSchemaListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexManager;

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
    protected $doctrineCommand;

    /**
     * @var UpdateSchemaDoctrineListener
     */
    protected $listener;

    protected function setUp()
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

        $this->indexManager = $this
            ->getMockBuilder('Oro\Bundle\SearchBundle\Engine\FulltextIndexManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new UpdateSchemaDoctrineListener($this->indexManager);
    }

    public function testNotRelatedCommand()
    {
        $command = $this->getMock('Oro\Bundle\SearchBundle\Command\IndexCommand', ['execute']);

        $this->eventMock
            ->expects($this->once())
            ->method('getCommand')
            ->will($this->returnValue($command));

        $this->indexManager
            ->expects($this->never())
            ->method('createIndexes');

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
            ->expects($this->once())
            ->method('getCommand')
            ->will($this->returnValue($this->doctrineCommand));

        $this->indexManager
            ->expects($this->never())
            ->method('createIndexes');

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

        $this->indexManager
            ->expects($this->once())
            ->method('createIndexes')
            ->will($this->returnValue(true));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln');

        $this->listener->onConsoleTerminate($this->eventMock);
    }
}
