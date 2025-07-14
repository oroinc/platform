<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\CallMethod;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CallMethodTest extends TestCase
{
    private CallMethod $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new CallMethod(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testInitializeNoMethod(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Method name parameter is required');

        $this->action->initialize([]);
    }

    public function testInitializeInvalidObject(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object must be valid property definition');

        $this->action->initialize(['method' => 'do', 'object' => 'stringData']);
    }

    public function testInitialize(): void
    {
        $options = [
            'method' => 'test',
            'object' => new PropertyPath('object'),
            'method_parameters' => null,
            'attribute' => 'test'
        ];
        self::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function testExecuteMethod(): void
    {
        $context = new ItemStub(['key' => 'value']);
        $options = [
            'method' => function ($a) {
                Assert::assertEquals('value', $a);
                return 'bar';
            },
            'method_parameters' => [new PropertyPath('key')],
            'attribute' => 'test'
        ];

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals(['key' => 'value', 'test' => 'bar'], $context->getData());
    }

    public function testExecuteClassMethod(): void
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

        self::assertEquals(['object' => $this, 'test' => 'bar'], $context->getData());
    }

    public function testExecuteClassMethodNoAssign(): void
    {
        $context = new ItemStub(['object' => $this]);
        $options = [
            'method' => 'assertCall',
            'object' => new PropertyPath('object'),
            'method_parameters' => ['test']
        ];

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals(['object' => $this], $context->getData());
    }

    public function testExecuteNullObject(): void
    {
        $context = new ItemStub(['object' => null]);
        $options = [
            'method' => 'assertCall',
            'object' => new PropertyPath('object'),
            'method_parameters' => ['test']
        ];

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals(['object' => null], $context->getData());
    }

    public function assertCall(mixed $a): string
    {
        self::assertEquals('test', $a);

        return 'bar';
    }
}
