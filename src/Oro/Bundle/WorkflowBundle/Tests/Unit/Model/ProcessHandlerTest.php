<?php
namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
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
        $this->processHandler->handleTrigger($processTrigger, null);
    }

    /**
     * @dataProvider handleTriggerProvider
     */
    public function testHandleTrigger($inputs, $expected)
    {
        $old = isset($inputs['old']) ? $inputs['old'] : null;
        $new = isset($inputs['new']) ? $inputs['new'] : null;

        $processTrigger = $this->assetHandleTrigger($expected);
        $this->processHandler->handleTrigger($processTrigger, $inputs['entity'], $old, $new);
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

    public function handleTriggerProvider()
    {
        return array(
            'only entity (create | delete event)' => array(
                'inputs' => array(
                    'entity' => new \DateTime()
                ),
                'expected' => new ProcessData(array(
                    'entity' => new \DateTime(),
                ))
            ),
            'entity and changeset (update event)' => array(
                'inputs' => array(
                    'entity' => new \DateTime(),
                    'old'    => array('label' => 'before'),
                    'new'    => array('label' => 'after')
                ),
                'expected' => new ProcessData(array(
                    'entity' => new \DateTime(),
                    'old'    => array('label' => 'before'),
                    'new'    => array('label' => 'after')
                ))
            ),
            'new is empty' => array(
                'inputs' => array(
                    'entity' => new \DateTime(),
                    'old'    => array('label' => 'before'),
                ),
                'expected' => new ProcessData(array(
                    'entity' => new \DateTime(),
                    'old'    => array('label' => 'before'),
                    'new'    => null
                ))
            ),
            'old is empty' => array(
                'inputs' => array(
                    'entity' => new \DateTime(),
                    'new'    => array('label' => 'after')
                ),
                'expected' => new ProcessData(array(
                    'entity' => new \DateTime(),
                    'old'    => null,
                    'new'    => array('label' => 'after')
                ))
            )
        );
    }

    /**
     * @dataProvider handleJobProviderException
     */
    public function testHandleJobException($event, $data, $exceptionType, $exceptionMessage)
    {
        $processTrigger = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger');
        $processTrigger->expects($this->once())
            ->method('getEvent')
            ->will($this->returnValue($event));

        $processJob = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessJob');
        $processJob->expects($this->once())
            ->method('getProcessTrigger')
            ->will($this->returnValue($processTrigger));
        $processJob->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $this->setExpectedException($exceptionType, $exceptionMessage);

        $this->processHandler->handleJob($processJob);
    }

    public function handleJobProviderException()
    {
        $unexpectedEvent = 'some-test-unexpected-event';
        return array(
            'empty entity' => array(
                'event'            => 'delete',
                'data'             => array('entity' => null),
                'exceptionType'    => 'Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Invalid process job data for the delete event. Entity can not be empty.'
            ),
            'wrong entity format' => array(
                'event'            => 'delete',
                'data'             => array('entity' => 'some-string'),
                'exceptionType'    => 'Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Invalid process job data for the delete event. Entity must be an object.'
            ),
            'unexpected event' => array(
                'event'            => $unexpectedEvent,
                'data'             => array(),
                'exceptionType'    => 'Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => sprintf('Got invalid or unregister event "%s"', $unexpectedEvent)
            )
        );
    }

    /**
     * @dataProvider handleJobProvider
     */
    public function testHandleJob($event, $data, $expected)
    {
        $processTrigger = $this->assetHandleTrigger($expected);
        $processTrigger->expects($this->once())
            ->method('getEvent')
            ->will($this->returnValue($event));

        $processJob = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessJob');
        $processJob->expects($this->once())
            ->method('getProcessTrigger')
            ->will($this->returnValue($processTrigger));
        $processJob->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        if ($event == ProcessTrigger::EVENT_DELETE) {
            $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->getMock();
            $repository->expects($this->never())->method('findEntity');

            $this->entityManager->expects($this->never())->method('getRepository');
        } else {
            $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->setMethods(array('findEntity'))
                ->getMock();
            $repository->expects($this->any())
                ->method('findEntity')
                ->with($processJob)
                ->will($this->returnValue($data['entity']));

            $this->entityManager->expects($this->once())
                ->method('getRepository')
                ->with('OroWorkflowBundle:ProcessJob')
                ->will($this->returnValue($repository));
        }

        $this->processHandler->handleJob($processJob);
    }

    public function handleJobProvider()
    {
        $entity = new \DateTime();
        return array(
            'event create' => array(
                'event' => ProcessTrigger::EVENT_CREATE,
                'data'  => new ProcessData(array(
                    'entity' => $entity
                )),
                'expected' => new ProcessData(array(
                    'entity' => $entity
                ))
            ),
            'event update' => array(
                'event' => ProcessTrigger::EVENT_UPDATE,
                'data'  => new ProcessData(array(
                    'entity' => new \DateTime(),
                    'old'    => array('label' => 'before'),
                    'new'    => array('label' => 'after'),
                )),
                'expected' => new ProcessData(array(
                    'entity' => new \DateTime(),
                    'old'    => array('label' => 'before'),
                    'new'    => array('label' => 'after'),
                ))
            ),
            'event delete' => array(
                'event' => ProcessTrigger::EVENT_DELETE,
                'data'  => new ProcessData(array(
                    'entity' => new \DateTime()
                )),
                'expected' => new ProcessData(array(
                    'entity' => new \DateTime()
                ))
            )
        );
    }
}
