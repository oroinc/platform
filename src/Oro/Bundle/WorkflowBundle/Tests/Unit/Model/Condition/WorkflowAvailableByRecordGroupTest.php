<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Condition\WorkflowAvailableByRecordGroup;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class WorkflowAvailableByRecordGroupTest extends \PHPUnit\Framework\TestCase
{
    const GROUP_NAME = 'test_group_name';
    const ENTITY_CLASS = 'stdClass';

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    /** @var WorkflowAvailableByRecordGroup */
    protected $condition;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->condition = new WorkflowAvailableByRecordGroup($this->manager);
    }

    protected function tearDown()
    {
        unset($this->condition, $this->manager);
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowAvailableByRecordGroup::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param array $options
     * @param bool $expectedResult
     */
    public function testEvaluate(array $options, $expectedResult)
    {
        $this->manager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->willReturnCallback(
                function ($entityClass) {
                    return $entityClass === self::ENTITY_CLASS ? [$this->createWorkflow()] : [];
                }
            );

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
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
     * @return Workflow
     */
    protected function createWorkflow()
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups([self::GROUP_NAME]);

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $workflow;
    }

    /**
     * @dataProvider initializeExceptionProvider
     *
     * @param array $options
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $options, $exception, $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionProvider()
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
