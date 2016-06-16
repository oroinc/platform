<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Model\Action\TransitWorkflow;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\Action\Model\ContextAccessor;

class TransitWorkflowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TransitWorkflow
     */
    protected $action;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WorkflowManager
     */
    protected $workflowManager;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->setMethods(
                [
                    'getWorkflowItem',
                    'transit',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new TransitWorkflow(new ContextAccessor(), $this->workflowManager);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown()
    {
        unset($this->router, $this->action);
    }

    public function testExecuteWorks()
    {
        $expectedEntity = new \stdClass();
        $expectedParameter = new \DateTime();
        $context = new ItemStub();
        $context->test = $expectedEntity;
        $context->parameter = $expectedParameter;

        $options = [
            'entity' => new PropertyPath('test'),
            'transition' => 'test_transition',
            'workflow' => 'test_workflow',
            'data' => [
                'scalar' => 'value',
                'path' => new PropertyPath('parameter'),
            ],
        ];

        $expectedData = array_merge($options['data'], ['path' => $expectedParameter]);

        $workflowData = $this->getMock('Oro\Bundle\WorkflowBundle\Model\WorkflowData');
        $workflowData->expects($this->once())
            ->method('add')
            ->with($expectedData);

        $expectedWorkflowItem = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem');
        $expectedWorkflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($expectedEntity, $options['workflow'])
            ->will($this->returnValue($expectedWorkflowItem));

        $this->workflowManager->expects($this->once())
            ->method('transit')
            ->with($expectedWorkflowItem, $options['transition'])
            ->will($this->returnValue($expectedWorkflowItem));

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\ActionException
     * @expectedExceptionMessage Cannot transit workflow, instance of "stdClass" doesn't have workflow item.
     */
    public function testExecuteFailsWhenThereIsNoWorkflowItem()
    {
        $expectedEntity = new \stdClass();
        $context = new ItemStub();
        $context->test = $expectedEntity;

        $options = [
            'entity' => new PropertyPath('test'),
            'transition' => 'test_transition',
            'workflow' => 'test_workflow'
        ];

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($expectedEntity, $options['workflow'])
            ->will($this->returnValue(null));

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @param array $options
     * @param string $expectedEntity
     * @param string $expectedTransition
     * @param string $expectedWorkflow
     * @param array $expectedData
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options, $expectedEntity, $expectedTransition, $expectedWorkflow, $expectedData = [])
    {
        $this->action->initialize($options);
        $this->assertAttributeEquals($expectedEntity, 'entity', $this->action);
        $this->assertAttributeEquals($expectedTransition, 'transition', $this->action);
        $this->assertAttributeEquals($expectedWorkflow, 'workflow', $this->action);
        $this->assertAttributeEquals($expectedData, 'data', $this->action);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'associated array options' => [
                'options' => [
                    'entity' => new PropertyPath('test'),
                    'transition' => 'test_transition',
                    'workflow' => 'test_workflow'
                ],
                'expectedEntity' => new PropertyPath('test'),
                'expectedTransition' => 'test_transition',
                'expectedWorkflow' => 'test_workflow',
            ],
            'associated array options with data' => [
                'options' => [
                    'entity' => new PropertyPath('test'),
                    'transition' => 'test_transition',
                    'workflow' => 'test_workflow',
                    'data' => [
                        'scalar' => 'value',
                        'path' => new PropertyPath('parameter'),
                    ]
                ],
                'expectedEntity' => new PropertyPath('test'),
                'expectedTransition' => 'test_transition',
                'expectedWorkflow' => 'test_workflow',
                'expectedData' => [
                    'scalar' => 'value',
                    'path' => new PropertyPath('parameter'),
                ],
            ],
            'indexed array options' => [
                'options' => [
                    new PropertyPath('test'),
                    'test_transition',
                    'test_workflow'
                ],
                'expectedEntity' => new PropertyPath('test'),
                'expectedTransition' => 'test_transition',
                'expectedWorkflow' => 'test_workflow',
            ],
            'indexed array options with data' => [
                'options' => [
                    new PropertyPath('test'),
                    'test_transition',
                    'test_workflow',
                    [
                        'scalar' => 'value',
                        'path' => new PropertyPath('parameter'),
                    ]
                ],
                'expectedEntity' => new PropertyPath('test'),
                'expectedTransition' => 'test_transition',
                'expectedWorkflow' => 'test_workflow',
                'expectedData' => [
                    'scalar' => 'value',
                    'path' => new PropertyPath('parameter'),
                ],
            ],
        ];
    }

    /**
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage)
    {
        $this->setExpectedException($exceptionName, $exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            'no entity' => [
                'options' => [],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Option "entity" is required.',
            ],
            'no transition provided' => [
                'options' => [
                    'entity' => new PropertyPath('test'),
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Option "transition" is required.',
            ],
            'no workflow provided' => [
                'options' => [
                    'entity' => new PropertyPath('test'),
                    'transition' => 'test_transition',
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Option "workflow" is required.',
            ],
        ];
    }
}
