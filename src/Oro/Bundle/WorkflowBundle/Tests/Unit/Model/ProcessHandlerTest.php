<?php
namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;

class ProcessHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $processFactory;

    /**
     * @var ProcessHandler
     */
    protected $processHandler;

    protected function setUp()
    {
        $this->entityManager  = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processFactory = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processHandler = new ProcessHandler($this->processFactory, $this->entityManager);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Invalid data for the "handleTrigger" function. Entity parameter can not be empty.
     */
    public function testHandleTriggerException()
    {
        $processTrigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $processTrigger->expects($this->never())->method('getDefinition');

        $process = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process->expects($this->never())->method('execute');

        $this->processFactory->expects($this->never())->method('create');
        $this->processHandler->handleTrigger($processTrigger, new ProcessData(array('entity' => null)));
    }

    public function testHandleTrigger()
    {
        $processData = new ProcessData(array(
            'entity' => new \DateTime(),
            'old'    => array('label' => 'before'),
            'new'    => array('label' => 'after')
        ));

        $processTrigger = $this->assetHandleTrigger($processData);
        $this->processHandler->handleTrigger($processTrigger, $processData);
    }

    public function assetHandleTrigger($expected)
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
            ->with($expected)
            ->will($this->returnValue($processDefinition));

        $this->processFactory->expects($this->once())
            ->method('create')
            ->with($processDefinition)
            ->will($this->returnValue($process));

        return $processTrigger;
    }

    /**
     * @dataProvider handleJobProvider
     */
    public function testHandleJob($data)
    {
        $processTrigger = $this->assetHandleTrigger($data);

        $processJob = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessJob');
        $processJob->expects($this->once())
            ->method('getProcessTrigger')
            ->will($this->returnValue($processTrigger));
        $processJob->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $this->processHandler->handleJob($processJob);
    }

    public function handleJobProvider()
    {
        $entity = new \DateTime();
        return array(
            'event create or delete' => array(
                'data' => new ProcessData(array(
                    'entity' => $entity
                ))
            ),
            'event update' => array(
                'data' => new ProcessData(array(
                    'entity' => $entity,
                    'old'    => array('label' => 'before'),
                    'new'    => array('label' => 'after'),
                ))
            ),
        );
    }
}
