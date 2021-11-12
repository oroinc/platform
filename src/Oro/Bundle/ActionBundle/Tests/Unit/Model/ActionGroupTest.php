<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;
use Oro\Bundle\ActionBundle\Model\Parameter;
use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;

class ActionGroupTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $actionFactory;

    /** @var ExpressionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $conditionFactory;

    /** @var ActionGroup */
    private $actionGroup;

    protected function setUp(): void
    {
        $this->actionFactory = $this->createMock(ActionFactoryInterface::class);
        $this->conditionFactory = $this->createMock(ExpressionFactory::class);

        $this->actionGroup = new ActionGroup(
            $this->actionFactory,
            $this->conditionFactory,
            new ParameterAssembler(),
            $this->createMock(ParametersResolver::class),
            new ActionGroupDefinition()
        );
    }

    /**
     * @dataProvider executeProvider
     */
    public function testExecute(
        ActionData $data,
        ActionInterface $action,
        ConfigurableCondition $condition,
        string $actionGroupName,
        string $exceptionMessage = ''
    ) {
        $this->actionGroup->getDefinition()->setName($actionGroupName);
        $this->actionGroup->getDefinition()->setActions(['action1']);
        $this->actionGroup->getDefinition()->setConditions(['condition1']);

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableAction::ALIAS)
            ->willReturn($action);

        $this->conditionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS)
            ->willReturn($condition);

        $errors = new ArrayCollection();

        if ($exceptionMessage) {
            $this->expectException(ForbiddenActionGroupException::class);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->assertSame($data, $this->actionGroup->execute($data, $errors));

        $this->assertEmpty($errors->toArray());
    }

    public function executeProvider(): array
    {
        $data = new ActionData(['data' => new \stdClass()]);

        return [
            '!isConditionAllowed' => [
                'data' => $data,
                'action' => $this->createActionGroup($this->never(), $data),
                'condition' => $this->createCondition($this->exactly(1), $data, false),
                'actionGroupName' => 'TestName2',
                'exception' => 'ActionGroup "TestName2" is not allowed'
            ],
            'isAllowed' => [
                'data' => $data,
                'action' => $this->createActionGroup($this->once(), $data),
                'condition' => $this->createCondition($this->exactly(1), $data, true),
                'actionGroupName' => 'TestName3',
            ],
        ];
    }

    /**
     * @dataProvider isAllowedProvider
     */
    public function testIsAllowed(ActionData $data, ?ConfigurableCondition $condition, bool $allowed)
    {
        if ($condition) {
            $this->actionGroup->getDefinition()->setConditions(['condition1']);
        }

        $this->conditionFactory->expects($condition ? $this->once() : $this->never())
            ->method('create')
            ->willReturn($condition);

        $this->assertEquals($allowed, $this->actionGroup->isAllowed($data));
    }

    public function isAllowedProvider(): array
    {
        $data = new ActionData(['data' => 'value']);

        return [
            'no conditions' => [
                'data' => $data,
                'condition' => null,
                'allowed' => true,
            ],
            '!isConditionAllowed' => [
                'data' => $data,
                'condition' => $this->createCondition($this->once(), $data, false),
                'allowed' => false,
            ],
            'allowed' => [
                'data' => $data,
                'condition' => $this->createCondition($this->once(), $data, true),
                'allowed' => true,
            ],
        ];
    }

    /**
     * @dataProvider getParametersProvider
     */
    public function testGetParameters(array $config, array $expected)
    {
        if ($config) {
            $this->actionGroup->getDefinition()->setParameters($config);
        }

        $this->assertEquals($expected, $this->actionGroup->getParameters());
    }

    public function getParametersProvider(): array
    {
        $parameter1 = new Parameter('parameter1');

        return [
            'no parameters' => [
                'config' => [],
                'expected' => [],
            ],
            '1 parameter' => [
                'config' => ['parameter1' => []],
                'expected' => ['parameter1' => $parameter1],
            ],
        ];
    }

    private function createActionGroup(
        \PHPUnit\Framework\MockObject\Rule\InvocationOrder $expects,
        ActionData $data
    ): ActionInterface {
        $action = $this->createMock(ActionInterface::class);
        $action->expects($expects)
            ->method('execute')
            ->with($data);

        return $action;
    }

    private function createCondition(
        \PHPUnit\Framework\MockObject\Rule\InvocationOrder $expects,
        ActionData $data,
        bool $returnValue
    ): ConfigurableCondition {
        $condition = $this->createMock(ConfigurableCondition::class);
        $condition->expects($expects)
            ->method('evaluate')
            ->with($data)
            ->willReturn($returnValue);

        return $condition;
    }
}
