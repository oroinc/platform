<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Action\StartWorkflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class StartWorkflowTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var StartWorkflow */
    private $action;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->action = new StartWorkflow(new ContextAccessor(), $this->workflowManager);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->action->initialize($options);
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function optionsDataProvider(): array
    {
        $workflowItem = $this->createWorkflowItem();

        $actualContext = new ItemStub(
            [
                'workflowName' => 'acmeWorkflow',
                'entityValue' => new \DateTime('now'),
                'startTransition' => 'acmeStartTransition',
                'someKey' => 'someValue'
            ]
        );

        $expectedContext = clone $actualContext;
        $expectedContext->workflowItem = $workflowItem;

        return [
            'minimum options' => [
                'options' => [
                    'name' => $actualContext->workflowName,
                    'attribute' => new PropertyPath('workflowItem'),
                ],
                'actualContext' => $actualContext,
                'expectedContext' => $expectedContext,
            ],
            'maximum plain option' => [
                'options' => [
                    'name' => $actualContext->workflowName,
                    'attribute' => new PropertyPath('workflowItem'),
                    'entity' => new PropertyPath('entityValue'),
                    'transition' => $actualContext->startTransition,
                    'data' => [
                        'plainData' => 'plainDataValue',
                    ]
                ],
                'actualContext' => $actualContext,
                'expectedContext' => $expectedContext,
                'expectedData' => [
                    'plainData' => 'plainDataValue',
                ]
            ],
            'maximum property path options' => [
                'options' => [
                    'name' => new PropertyPath('workflowName'),
                    'attribute' => new PropertyPath('workflowItem'),
                    'entity' => new PropertyPath('entityValue'),
                    'transition' => new PropertyPath('startTransition'),
                    'data' => [
                        'propertyData' => new PropertyPath('someKey'),
                    ],
                ],
                'actualContext' => $actualContext,
                'expectedContext' => $expectedContext,
                'expectedData' => [
                    'propertyData' => $expectedContext->someKey,
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
            'no name' => [
                'options' => [],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Workflow name parameter is required',
            ],
            'no attribute' => [
                'options' => [
                    'name' => 'acmeWorkflow'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribute name parameter is required',
            ],
            'invalid attribute' => [
                'options' => [
                    'name' => 'acmeWorkflow',
                    'attribute' => 'notPropertyPath'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribute must be valid property definition',
            ],
            'invalid entity' => [
                'options' => [
                    'name' => 'acmeWorkflow',
                    'attribute' => new PropertyPath('workflowItem'),
                    'entity' => 'notPropertyPath'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Entity must be valid property definition',
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testExecute(
        array $options,
        ItemStub $actualContext,
        ItemStub $expectedContext,
        array $expectedData = []
    ) {
        $expectedWorkflowName = $expectedContext->workflowName;
        $expectedEntity = !empty($options['entity']) ? $expectedContext->entityValue : null;
        $expectedTransition = !empty($options['transition']) ? $expectedContext->startTransition : null;
        $expectedWorkflowItem = $expectedContext->workflowItem;

        $this->workflowManager->expects($this->once())
            ->method('startWorkflow')
            ->with($expectedWorkflowName, $expectedEntity, $expectedTransition, $expectedData)
            ->willReturn($expectedWorkflowItem);

        $this->action->initialize($options);
        $this->action->execute($actualContext);

        $this->assertEquals($expectedContext->getData(), $actualContext->getData());
    }

    private function createWorkflowItem(): WorkflowItem
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setId(1);

        return $workflowItem;
    }

    public function testExecuteEntityNotAnObject()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Entity value must be an object');

        $options = [
            'name' => 'acmeWorkflow',
            'attribute' => new PropertyPath('workflowItem'),
            'entity' => new PropertyPath('entityValue'),
        ];
        $context = new ItemStub(
            [
                'workflowName' => 'acmeWorkflow',
                'entityValue' => 'notAnObject',
            ]
        );

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
