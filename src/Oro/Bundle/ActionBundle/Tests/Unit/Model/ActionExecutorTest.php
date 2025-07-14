<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\ConfigExpression\ExpressionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class ActionExecutorTest extends TestCase
{
    private ActionFactoryInterface&MockObject $actionFactory;
    private ActionGroupRegistry&MockObject $actionGroupRegistry;
    private ExpressionFactory&MockObject $expressionFactory;
    private ActionExecutor $actionExecutor;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionFactory = $this->createMock(ActionFactoryInterface::class);
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);
        $this->expressionFactory = $this->createMock(ExpressionFactory::class);

        $this->actionExecutor = new ActionExecutor(
            $this->actionFactory,
            $this->actionGroupRegistry,
            $this->expressionFactory
        );
    }

    public function testExecuteAction(): void
    {
        $actionName = 'test_action';
        $data = ['key' => 'value'];
        $context = new ActionData($data);
        $preparedData = ['key' => new PropertyPath('key')];

        $action = $this->createMock(ActionInterface::class);

        $this->actionFactory->expects($this->once())
            ->method('create')
            ->with($actionName, $preparedData)
            ->willReturn($action);

        $action->expects($this->once())
            ->method('execute')
            ->with($context)
            ->willReturn($context);

        $this->assertEquals($context, $this->actionExecutor->executeAction($actionName, $data));
    }

    public function testExecuteActionGroup(): void
    {
        $actionGroupName = 'test_action_group';
        $data = ['key' => 'value'];
        $context = new ActionData($data);
        $actionGroup = $this->createMock(ActionGroup::class);

        $this->actionGroupRegistry->expects($this->once())
            ->method('get')
            ->with($actionGroupName)
            ->willReturn($actionGroup);

        $actionGroup->expects($this->once())
            ->method('execute')
            ->with($context)
            ->willReturn($context);

        $this->assertEquals($context, $this->actionExecutor->executeActionGroup($actionGroupName, $data));
    }

    public function testEvaluateExpression(): void
    {
        $expressionName = 'test_expression';
        $data = ['key' => 'value'];
        $preparedData = ['key' => new PropertyPath('key')];
        $errors = $this->createMock(\ArrayAccess::class);
        $message = 'Test message';

        $expression = $this->createMock(ExpressionInterface::class);

        $this->expressionFactory->expects($this->once())
            ->method('create')
            ->with($expressionName, $preparedData)
            ->willReturn($expression);

        $expression->expects($this->once())
            ->method('setMessage')
            ->with($message);

        $expression->expects($this->once())
            ->method('evaluate')
            ->with($data, $errors)
            ->willReturn(true);

        $this->assertTrue($this->actionExecutor->evaluateExpression($expressionName, $data, $errors, $message));
    }
}
