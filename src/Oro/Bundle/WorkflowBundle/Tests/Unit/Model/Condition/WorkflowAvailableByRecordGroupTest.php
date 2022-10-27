<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Condition\WorkflowAvailableByRecordGroup;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class WorkflowAvailableByRecordGroupTest extends \PHPUnit\Framework\TestCase
{
    private const GROUP_NAME = 'test_group_name';
    private const ENTITY_CLASS = 'stdClass';

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var WorkflowAvailableByRecordGroup */
    private $condition;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(WorkflowManager::class);

        $this->condition = new WorkflowAvailableByRecordGroup($this->manager);
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowAvailableByRecordGroup::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, bool $expectedResult)
    {
        $this->manager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->willReturnCallback(function ($entityClass) {
                return $entityClass === self::ENTITY_CLASS ? [$this->createWorkflow()] : [];
            });

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate([]));
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

    private function createWorkflow(): Workflow
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups([self::GROUP_NAME]);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $workflow;
    }

    /**
     * @dataProvider initializeExceptionProvider
     */
    public function testInitializeException(array $options, string $exception, string $exceptionMessage)
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
