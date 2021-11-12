<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Action\TransitWorkflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Exception\ActionException;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class TransitWorkflowTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var TransitWorkflow */
    private $action;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->action = new TransitWorkflow(new ContextAccessor(), $this->workflowManager);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
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

        $workflowData = $this->createMock(WorkflowData::class);
        $workflowData->expects($this->once())
            ->method('add')
            ->with($expectedData);

        $expectedWorkflowItem = $this->createMock(WorkflowItem::class);
        $expectedWorkflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($expectedEntity, $options['workflow'])
            ->willReturn($expectedWorkflowItem);

        $this->workflowManager->expects($this->once())
            ->method('transit')
            ->with($expectedWorkflowItem, $options['transition'])
            ->willReturn($expectedWorkflowItem);

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteFailsWhenThereIsNoWorkflowItem()
    {
        $this->expectException(ActionException::class);
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
            ->willReturn(null);

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(
        array $options,
        PropertyPath $expectedEntity,
        string $expectedTransition,
        string $expectedWorkflow,
        array $expectedData = []
    ) {
        $this->action->initialize($options);
        self::assertEquals($expectedEntity, ReflectionUtil::getPropertyValue($this->action, 'entity'));
        self::assertEquals($expectedTransition, ReflectionUtil::getPropertyValue($this->action, 'transition'));
        self::assertEquals($expectedWorkflow, ReflectionUtil::getPropertyValue($this->action, 'workflow'));
        self::assertEquals($expectedData, ReflectionUtil::getPropertyValue($this->action, 'data'));
    }

    public function optionsDataProvider(): array
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
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exceptionName, string $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            'no entity' => [
                'options' => [],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Option "entity" is required.',
            ],
            'no transition provided' => [
                'options' => [
                    'entity' => new PropertyPath('test'),
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Option "transition" is required.',
            ],
            'no workflow provided' => [
                'options' => [
                    'entity' => new PropertyPath('test'),
                    'transition' => 'test_transition',
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Option "workflow" is required.',
            ],
        ];
    }
}
