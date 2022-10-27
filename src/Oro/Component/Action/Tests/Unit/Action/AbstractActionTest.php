<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Event\ExecuteActionEvent;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\Action\Tests\Unit\Action\Stub\ArrayCondition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AbstractActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var AbstractAction|\PHPUnit\Framework\MockObject\MockObject */
    private $action;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = $this->getMockBuilder(AbstractAction::class)
            ->setConstructorArgs([new ContextAccessor()])
            ->getMockForAbstractClass();
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testSetCondition()
    {
        $condition = $this->createMock(ExpressionInterface::class);
        $this->action->setCondition($condition);
        self::assertSame($condition, ReflectionUtil::getPropertyValue($this->action, 'condition'));
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(bool $expectedAllowed, bool $conditionAllowed = null)
    {
        $context = ['key' => 'value'];

        if ($expectedAllowed) {
            $this->action->expects(self::once())
                ->method('executeAction')
                ->with($context);
            $this->dispatcher->expects(self::exactly(2))
                ->method('dispatch')
                ->withConsecutive(
                    [new ExecuteActionEvent($context, $this->action), ExecuteActionEvents::HANDLE_BEFORE],
                    [new ExecuteActionEvent($context, $this->action), ExecuteActionEvents::HANDLE_AFTER]
                );
        } else {
            $this->action->expects(self::never())
                ->method('executeAction');
            $this->dispatcher->expects(self::never())
                ->method('dispatch');
        }

        if ($conditionAllowed !== null) {
            $condition = new ArrayCondition(['allowed' => $conditionAllowed]);
            $this->action->setCondition($condition);
        }

        $this->action->execute($context);
    }

    public function executeDataProvider(): array
    {
        return [
            'no condition' => [
                'expectedAllowed' => true
            ],
            'allowed condition' => [
                'expectedAllowed'  => true,
                'conditionAllowed' => true
            ],
            'denied condition' => [
                'expectedAllowed'  => false,
                'conditionAllowed' => false
            ],
        ];
    }
}
