<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Event\ExecuteActionEvent;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\Action\Tests\Unit\Action\Stub\ArrayCondition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\ExpressionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AbstractActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractAction|MockObject */
    protected $action;

    /** @var EventDispatcherInterface|MockObject */
    protected $dispatcher;

    protected function setUp(): void
    {
        $this->action = $this->getMockBuilder(AbstractAction::class)
            ->setConstructorArgs([new ContextAccessor()])
            ->getMockForAbstractClass();

        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($this->dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->action);
    }

    public function testSetCondition()
    {
        $action = new class(new ContextAccessor()) extends AbstractAction {
            protected function executeAction($context)
            {
            }

            public function initialize(array $options)
            {
            }

            public function xgetCondition(): ExpressionInterface
            {
                return $this->condition;
            }
        };

        /** @var ExpressionInterface|MockObject $condition */
        $condition = $this->getMockBuilder(ExpressionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $action->setCondition($condition);

        static::assertSame($condition, $action->xgetCondition());
    }

    /**
     * @param bool $expectedAllowed
     * @param bool|null $conditionAllowed
     * @dataProvider executeDataProvider
     */
    public function testExecute($expectedAllowed, $conditionAllowed = null)
    {
        $context = ['key' => 'value'];

        if ($expectedAllowed) {
            $this->action->expects(static::once())->method('executeAction')->with($context);
            $this->dispatcher->expects(static::exactly(2))
                ->method('dispatch')
                ->withConsecutive(
                    [new ExecuteActionEvent($context, $this->action), ExecuteActionEvents::HANDLE_BEFORE],
                    [new ExecuteActionEvent($context, $this->action), ExecuteActionEvents::HANDLE_AFTER]
                );
        } else {
            $this->action->expects(static::never())->method('executeAction');
            $this->dispatcher->expects(static::never())->method('dispatch');
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
