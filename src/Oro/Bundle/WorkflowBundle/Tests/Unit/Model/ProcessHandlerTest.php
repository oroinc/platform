<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Event\ProcessEvents;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;

class ProcessHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ProcessHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessLogger')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->handler = new ProcessHandler($this->factory, $this->logger, $this->eventDispatcher);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Invalid process data. Entity can not be empty.
     */
    public function testHandleTriggerException()
    {
        $processTrigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $processTrigger->expects($this->never())->method('getDefinition');

        $process = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process->expects($this->never())->method('execute');

        $this->factory->expects($this->never())->method('create');
        $this->handler->handleTrigger($processTrigger, new ProcessData(array('entity' => null)));
    }

    public function testHandleTrigger()
    {
        $processData = new ProcessData(array(
            'data' => new \DateTime(),
            'old'  => array('label' => 'before'),
            'new'  => array('label' => 'after')
        ));

        $processTrigger = $this->prepareHandleTrigger($processData);
        $this->handler->handleTrigger($processTrigger, $processData);
    }

    public function prepareHandleTrigger($processData)
    {
        $processDefinition = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition');
        $processTrigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $processTrigger->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($processDefinition));

        $process = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process->expects($this->once())
            ->method('execute')
            ->with($processData)
            ->will($this->returnValue($processDefinition));

        $this->factory->expects($this->once())
            ->method('create')
            ->with($processDefinition)
            ->will($this->returnValue($process));
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Process executed', $processTrigger, $processData);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(
                ProcessEvents::HANDLE_BEFORE,
                $this->callback(
                    function ($event) use ($processTrigger, $processData) {
                        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent', $event);
                        $this->assertAttributeSame($processTrigger, 'processTrigger', $event);
                        $this->assertAttributeSame($processData, 'processData', $event);
                        return true;
                    }
                )
            );
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                ProcessEvents::HANDLE_AFTER,
                $this->callback(
                    function ($event) use ($processTrigger, $processData, $process) {
                        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent', $event);
                        $this->assertAttributeSame($processTrigger, 'processTrigger', $event);
                        $this->assertAttributeSame($processData, 'processData', $event);
                        return true;
                    }
                )
            );

        return $processTrigger;
    }

    /**
     * @dataProvider handleJobProvider
     */
    public function testHandleJob($data)
    {
        $processTrigger = $this->prepareHandleTrigger($data);

        $processJob = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessJob');
        $processJob->expects($this->once())
            ->method('getProcessTrigger')
            ->will($this->returnValue($processTrigger));
        $processJob->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $this->handler->handleJob($processJob);
    }

    public function handleJobProvider()
    {
        $entity = new \DateTime();
        return array(
            'event create or delete' => array(
                'data' => new ProcessData(array(
                    'data' => $entity
                ))
            ),
            'event update' => array(
                'data' => new ProcessData(array(
                    'data' => $entity,
                    'old'  => array('label' => 'before'),
                    'new'  => array('label' => 'after'),
                ))
            ),
        );
    }

    public function testFinishTrigger()
    {
        $processTrigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $processData = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessData')
            ->disableOriginalConstructor()->getMock();

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                ProcessEvents::HANDLE_AFTER_FLUSH,
                $this->callback(
                    function ($event) use ($processTrigger, $processData) {
                        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent', $event);
                        $this->assertAttributeSame($processTrigger, 'processTrigger', $event);
                        $this->assertAttributeSame($processData, 'processData', $event);
                        return true;
                    }
                )
            );

        $this->handler->finishTrigger($processTrigger, $processData);
    }

    public function testFinishJob()
    {
        $processTrigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $processData = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessData')
            ->disableOriginalConstructor()->getMock();

        $processJob = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessJob');
        $processJob->expects($this->once())
            ->method('getProcessTrigger')
            ->will($this->returnValue($processTrigger));
        $processJob->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($processData));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                ProcessEvents::HANDLE_AFTER_FLUSH,
                $this->callback(
                    function ($event) use ($processTrigger, $processData) {
                        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent', $event);
                        $this->assertAttributeSame($processTrigger, 'processTrigger', $event);
                        $this->assertAttributeSame($processData, 'processData', $event);
                        return true;
                    }
                )
            );

        $this->handler->finishJob($processJob);
    }
}
