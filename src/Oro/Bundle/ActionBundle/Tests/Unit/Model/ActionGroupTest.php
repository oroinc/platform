<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Event\ActionGroupEventDispatcher;
use Oro\Bundle\ActionBundle\Event\ActionGroupExecuteEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupGuardEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupPreExecuteEvent;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;

class ActionGroupTest extends TestCase
{
    private ActionFactory|MockObject $actionFactory;
    private ExpressionFactory|MockObject $conditionFactory;
    private ActionGroupEventDispatcher|MockObject $eventDispatcher;
    private ActionGroupDefinition $definition;

    private ActionGroup $actionGroup;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionFactory = $this->createMock(ActionFactoryInterface::class);
        $this->conditionFactory = $this->createMock(ExpressionFactory::class);
        $this->eventDispatcher = $this->createMock(ActionGroupEventDispatcher::class);
        $this->definition = new ActionGroupDefinition();

        $this->actionGroup = new ActionGroup(
            $this->actionFactory,
            $this->conditionFactory,
            new ParameterAssembler(),
            $this->createMock(ParametersResolver::class),
            $this->eventDispatcher,
            $this->definition
        );
    }

    public function testExecute()
    {
        $data = new ActionData(['data' => new \stdClass()]);
        $action = $this->createActionGroup($this->once(), $data);
        $condition = $this->createCondition($this->exactly(1), $data, true);

        $this->definition->setName('test');
        $this->definition->setActions(['action1']);
        $this->definition->setConditions(['condition1']);

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableAction::ALIAS)
            ->willReturn($action);

        $this->conditionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS)
            ->willReturn($condition);

        $errors = new ArrayCollection();
        $this->assertEventDispatchForExecute($data, $errors);

        $this->actionGroup->execute($data, $errors);
    }

    public function testExecuteNotAllowedByCondition()
    {
        $data = new ActionData(['data' => new \stdClass()]);
        $action = $this->createActionGroup($this->never(), $data);
        $condition = $this->createCondition($this->exactly(1), $data, false);

        $this->definition->setName('TestName2');
        $this->definition->setActions(['action1']);
        $this->definition->setConditions(['condition1']);

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableAction::ALIAS)
            ->willReturn($action);

        $this->conditionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS)
            ->willReturn($condition);

        $errors = new ArrayCollection();

        $event = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event);

        $this->expectException(ForbiddenActionGroupException::class);
        $this->expectExceptionMessage('ActionGroup "TestName2" is not allowed');

        $this->assertSame($data, $this->actionGroup->execute($data, $errors));

        $this->assertEmpty($errors->toArray());
    }

    public function testExecuteNotAllowedByEvent()
    {
        $data = new ActionData(['data' => new \stdClass()]);

        $this->definition->setName('TestName2');
        $this->definition->setActions(['action1']);
        $this->definition->setConditions(['condition1']);

        $this->actionFactory->expects($this->never())
            ->method('create');

        $this->conditionFactory->expects($this->never())
            ->method('create');

        $errors = new ArrayCollection();

        $event = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(function (ActionGroupGuardEvent $event) {
                $event->setAllowed(false);
            });

        $this->expectException(ForbiddenActionGroupException::class);
        $this->expectExceptionMessage('ActionGroup "TestName2" is not allowed');

        $this->actionGroup->execute($data, $errors);
    }

    /**
     * @dataProvider isAllowedProvider
     */
    public function testIsAllowed(ActionData $data, ?ConfigurableCondition $condition, bool $allowed)
    {
        if ($condition) {
            $this->definition->setConditions(['condition1']);
        }

        $errors = new ArrayCollection();
        $event = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event);

        $this->conditionFactory->expects($condition ? $this->once() : $this->never())
            ->method('create')
            ->willReturn($condition);

        $this->assertEquals($allowed, $this->actionGroup->isAllowed($data, $errors));
    }

    public function testIsAllowedDisallowedByEvent(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();

        $this->definition->setConditions(['condition1']);
        $this->conditionFactory->expects($this->never())
            ->method('create');

        $event = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(function (ActionGroupGuardEvent $event) {
                $event->setAllowed(false);
            });

        $this->assertFalse($this->actionGroup->isAllowed($data, $errors));
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
            $this->definition->setParameters($config);
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
        InvocationOrder $expects,
        ActionData $data
    ): ActionInterface {
        $action = $this->createMock(ActionInterface::class);
        $action->expects($expects)
            ->method('execute')
            ->with($data);

        return $action;
    }

    private function createCondition(
        InvocationOrder $expects,
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

    private function assertEventDispatchForExecute(ActionData $data, ArrayCollection $errors): void
    {
        $preExecuteEvent = new ActionGroupPreExecuteEvent($data, $this->definition, $errors);
        $executeEvent = new ActionGroupExecuteEvent($data, $this->definition, $errors);
        $guardEvent = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$guardEvent],
                [$preExecuteEvent],
                [$executeEvent],
            );
    }
}
