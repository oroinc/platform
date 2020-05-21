<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\CallMethod;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CallMethodTest extends \PHPUnit\Framework\TestCase
{
    /** @var CallMethod */
    protected $action;

    protected function setUp(): void
    {
        $this->action = new class(new ContextAccessor()) extends CallMethod {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    public function testInitializeNoMethod()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Method name parameter is required');

        $this->action->initialize([]);
    }

    public function testInitializeInvalidObject()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object must be valid property definition');

        $this->action->initialize(['method' => 'do', 'object' => 'stringData']);
    }

    public function testInitialize()
    {
        $options = [
            'method' => 'test',
            'object' => new PropertyPath('object'),
            'method_parameters' => null,
            'attribute' => 'test'
        ];
        static::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        static::assertEquals($options, $this->action->xgetOptions());
    }

    public function testExecuteMethod()
    {
        $context = new ItemStub(['key' => 'value']);
        $options = [
            'method' => function ($a) {
                \PHPUnit\Framework\Assert::assertEquals('value', $a);
                return 'bar';
            },
            'method_parameters' => [new PropertyPath('key')],
            'attribute' => 'test'
        ];

        $this->action->initialize($options);
        $this->action->execute($context);

        static::assertEquals(['key' => 'value', 'test' => 'bar'], $context->getData());
    }

    public function testExecuteClassMethod()
    {
        $context = new ItemStub(['object' => $this]);
        $options = [
            'method' => 'assertCall',
            'object' => new PropertyPath('object'),
            'method_parameters' => ['test'],
            'attribute' => 'test'
        ];

        $this->action->initialize($options);
        $this->action->execute($context);

        static::assertEquals(['object' => $this, 'test' => 'bar'], $context->getData());
    }

    public function testExecuteClassMethodNoAssign()
    {
        $context = new ItemStub(['object' => $this]);
        $options = [
            'method' => 'assertCall',
            'object' => new PropertyPath('object'),
            'method_parameters' => ['test']
        ];

        $this->action->initialize($options);
        $this->action->execute($context);

        static::assertEquals(['object' => $this], $context->getData());
    }

    public function assertCall($a)
    {
        static::assertEquals('test', $a);
        return 'bar';
    }
}
