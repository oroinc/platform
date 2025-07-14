<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Condition\WorkflowAvailableByRecordGroup;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowAvailableByRecordGroupTest extends TestCase
{
    private const GROUP_NAME = 'test_group_name';
    private const ENTITY_CLASS = 'stdClass';

    private WorkflowManager&MockObject $workflowManager;
    private WorkflowAvailableByRecordGroup $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->condition = new WorkflowAvailableByRecordGroup($this->workflowManager);
    }

    private function getWorkflow(): Workflow
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups([self::GROUP_NAME]);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $workflow;
    }

    public function testGetName(): void
    {
        $this->assertEquals('workflow_available_by_record_group', $this->condition->getName());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, bool $expectedResult): void
    {
        $this->workflowManager->expects(self::any())
            ->method('getApplicableWorkflows')
            ->willReturnCallback(function ($entityClass) {
                return $entityClass === self::ENTITY_CLASS ? [$this->getWorkflow()] : [];
            });

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertEquals($expectedResult, $this->condition->evaluate([]));
    }

    public function evaluateDataProvider(): array
    {
        return [
            [
                'options' => ['group_name' => self::GROUP_NAME, 'entity_class' => self::ENTITY_CLASS],
                'expectedResult' => true
            ],
            [
                'options' => ['group_name' => 'unknown', 'entity_class' => self::ENTITY_CLASS],
                'expectedResult' => false
            ],
            [
                'options' => ['group_name' => self::GROUP_NAME, 'entity_class' => 'unknown'],
                'expectedResult' => false
            ]
        ];
    }

    /**
     * @dataProvider initializeExceptionProvider
     */
    public function testInitializeException(array $options, string $exception, string $exceptionMessage): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->condition->initialize($options);
    }

    public function initializeExceptionProvider(): array
    {
        return [
            [
                'options' => [],
                'exception' => InvalidArgumentException::class,
                'exceptionMessage' => 'Group name parameter is required'
            ],
            [
                'options' => ['group_name' => 'test'],
                'exception' => InvalidArgumentException::class,
                'exceptionMessage' => 'Entity class parameter is required'
            ]
        ];
    }
}
