<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\AssignValue;
use Oro\Component\Action\Action\UnsetValue;
use Oro\Component\ConfigExpression\ExpressionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnsetValueTest extends TestCase
{
    private AssignValue&MockObject $assignValue;
    private ActionInterface $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->assignValue = $this->createMock(AssignValue::class);

        $this->action = new UnsetValue($this->assignValue);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testExecute(): void
    {
        $context = [];
        $this->assignValue->expects($this->once())
            ->method('execute')
            ->with($context);
        $this->action->execute($context);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options, array $expected): void
    {
        $this->assignValue->expects($this->once())
            ->method('initialize')
            ->with($expected);

        $this->action->initialize($options);
    }

    public function optionsDataProvider(): array
    {
        return [
            [
                [], ['value' => null]
            ],
            [
                ['attribute' => 'test'], ['attribute' => 'test', 'value' => null]
            ],
            [
                ['test'], ['test', null]
            ]
        ];
    }

    public function testSetCondition(): void
    {
        $condition = $this->createMock(ExpressionInterface::class);

        $this->assignValue->expects($this->once())
            ->method('setCondition')
            ->with($condition);

        $this->action->setCondition($condition);
    }
}
