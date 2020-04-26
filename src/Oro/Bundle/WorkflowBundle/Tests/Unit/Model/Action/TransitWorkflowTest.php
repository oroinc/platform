<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Model\Action\TransitWorkflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class TransitWorkflowTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitWorkflow */
    protected $action;

    /** @var MockObject|WorkflowManager */
    protected $workflowManager;

    protected function setUp(): void
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->onlyMethods(['getWorkflowItem', 'transit',])
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new class(new ContextAccessor(), $this->workflowManager) extends TransitWorkflow {
            public function xgetEntity(): string
            {
                return $this->entity;
            }

            public function xgetTransition(): string
            {
                return $this->transition;
            }

            public function xgetWorkflow(): string
            {
                return $this->workflow;
            }

            public function xgetData(): array
            {
                return $this->data;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
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

        $workflowData = $this->createMock('Oro\Bundle\WorkflowBundle\Model\WorkflowData');
        $workflowData->expects($this->once())
            ->method('add')
            ->with($expectedData);

        $expectedWorkflowItem = $this->createMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem');
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

    public function testExecuteFailsWhenThereIsNoWorkflowItem()
    {
        $this->expectException(\Oro\Component\Action\Exception\ActionException::class);
        $this->expectExceptionMessage('Cannot transit workflow, instance of "stdClass" doesn\'t have workflow item.');

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
    public function testInitialize(
        array $options,
        $expectedEntity,
        $expectedTransition,
        $expectedWorkflow,
        array $expectedData = []
    ) {
        $this->action->initialize($options);
        static::assertEquals($expectedEntity, $this->action->xgetEntity());
        static::assertEquals($expectedTransition, $this->action->xgetTransition());
        static::assertEquals($expectedWorkflow, $this->action->xgetWorkflow());
        static::assertEquals($expectedData, $this->action->xgetData());
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
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
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
