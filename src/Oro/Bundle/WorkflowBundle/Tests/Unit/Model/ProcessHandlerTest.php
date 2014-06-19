<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
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

        $this->handler = new ProcessHandler($this->factory, $this->logger);
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
}
