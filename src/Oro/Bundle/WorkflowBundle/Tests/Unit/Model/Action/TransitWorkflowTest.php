<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Model\Action\TransitWorkflow;
use Oro\Bundle\ActionBundle\Model\ContextAccessor;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

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
                    'getWorkflowItemByEntity',
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
            ->method('getWorkflowItemByEntity')
            ->with($expectedEntity)
            ->will($this->returnValue($expectedWorkflowItem));

        $this->workflowManager->expects($this->once())
            ->method('transit')
            ->with($expectedWorkflowItem, $options['transition'])
            ->will($this->returnValue($expectedWorkflowItem));

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\ActionException
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
        ];

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemByEntity')
            ->with($expectedEntity)
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
     * @param array $expectedData
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options, $expectedEntity, $expectedTransition, $expectedData = [])
    {
        $this->action->initialize($options);
        $this->assertAttributeEquals($expectedEntity, 'entity', $this->action);
        $this->assertAttributeEquals($expectedTransition, 'transition', $this->action);
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
                ],
                'expectedEntity' => new PropertyPath('test'),
                'expectedTransition' => 'test_transition',
            ],
            'associated array options with data' => [
                'options' => [
                    'entity' => new PropertyPath('test'),
                    'transition' => 'test_transition',
                    'data' => [
                        'scalar' => 'value',
                        'path' => new PropertyPath('parameter'),
                    ]
                ],
                'expectedEntity' => new PropertyPath('test'),
                'expectedTransition' => 'test_transition',
                'expectedData' => [
                    'scalar' => 'value',
                    'path' => new PropertyPath('parameter'),
                ],
            ],
            'indexed array options' => [
                'options' => [
                    new PropertyPath('test'),
                    'test_transition',
                ],
                'expectedEntity' => new PropertyPath('test'),
                'expectedTransition' => 'test_transition',
            ],
            'indexed array options with data' => [
                'options' => [
                    new PropertyPath('test'),
                    'test_transition',
                    [
                        'scalar' => 'value',
                        'path' => new PropertyPath('parameter'),
                    ]
                ],
                'expectedEntity' => new PropertyPath('test'),
                'expectedTransition' => 'test_transition',
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
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Option "entity" is required.',
            ],
            'invalid route parameters' => [
                'options' => [
                    'entity' => new PropertyPath('test'),
                ],
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Option "transition" is required.',
            ],
        ];
    }
}
