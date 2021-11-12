<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Model\Process;
use Oro\Component\Action\Action\ActionAssembler;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;

class ProcessTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessDefinition|\PHPUnit\Framework\MockObject\MockObject */
    private $processDefinition;

    /** @var ExpressionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $conditionFactory;

    /** @var ActionAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $actionAssembler;

    /** @var Process */
    private $process;

    protected function setUp(): void
    {
        $this->processDefinition = $this->createMock(ProcessDefinition::class);
        $this->conditionFactory = $this->createMock(ExpressionFactory::class);
        $this->actionAssembler = $this->createMock(ActionAssembler::class);

        $this->process = new Process($this->actionAssembler, $this->conditionFactory, $this->processDefinition);
    }

    public function testExecute()
    {
        $context = ['context'];
        $configuration = ['config'];

        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->exactly(2))
            ->method('execute')
            ->with($context);

        $this->processDefinition->expects($this->once())
            ->method('getActionsConfiguration')
            ->willReturn($configuration);
        $this->actionAssembler->expects($this->once())
            ->method('assemble')
            ->willReturn($action);

        $this->process->execute($context);
        $this->process->execute($context);
    }

    public function testIsApplicableNoPreConditionsSection()
    {
        $expectedConditionConfiguration = [
            '@feature_resource_enabled' => [
                'resource' => '',
                'resource_type' => 'processes'
            ]
        ];
        $this->conditionFactory->expects($this->once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $expectedConditionConfiguration);

        $this->assertTrue($this->process->isApplicable([]));
    }

    public function testIsApplicableNoPreConditions()
    {
        $context = [];
        $conditionConfiguration = null;
        $expectedConditionConfiguration = [
            '@feature_resource_enabled' => [
                'resource' => '',
                'resource_type' => 'processes'
            ]
        ];

        $this->processDefinition->expects($this->once())
            ->method('getPreConditionsConfiguration')
            ->willReturn($conditionConfiguration);

        $this->conditionFactory->expects($this->once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $expectedConditionConfiguration);

        $this->assertTrue($this->process->isApplicable($context));
    }

    public function testIsApplicable()
    {
        $context = [];
        $conditionConfiguration = ['test' => []];
        $expectedConditionConfiguration = [
            '@and' => [
                [
                    '@feature_resource_enabled' => [
                        'resource' => '',
                        'resource_type' => 'processes'
                    ]
                ],
                ['test' => []]
            ]
        ];
        $condition = $this->createMock(ConfigurableCondition::class);
        $condition->expects($this->any())
            ->method('evaluate')
            ->with($context)
            ->willReturn(false);

        $this->processDefinition->expects($this->once())
            ->method('getPreConditionsConfiguration')
            ->willReturn($conditionConfiguration);

        $this->conditionFactory->expects($this->once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $expectedConditionConfiguration)
            ->willReturn($condition);

        $this->assertFalse($this->process->isApplicable($context));
        $this->assertFalse($this->process->isApplicable($context));
    }

    public function testExecutePreConditionsAreNotMet()
    {
        $context = [];
        $conditionConfiguration = ['test' => []];
        $expectedConditionConfiguration = [
            '@and' => [
                [
                    '@feature_resource_enabled' => [
                        'resource' => '',
                        'resource_type' => 'processes'
                    ]
                ],
                ['test' => []]
            ]
        ];
        $condition = $this->createMock(ConfigurableCondition::class);
        $condition->expects($this->any())
            ->method('evaluate')
            ->with($context)
            ->willReturn(false);

        $this->processDefinition->expects($this->once())
            ->method('getPreConditionsConfiguration')
            ->willReturn($conditionConfiguration);

        $this->conditionFactory->expects($this->once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $expectedConditionConfiguration)
            ->willReturn($condition);

        $this->processDefinition->expects($this->never())
            ->method('getActionsConfiguration');
        $this->actionAssembler->expects($this->never())
            ->method('assemble');

        $this->process->execute($context);
    }
}
