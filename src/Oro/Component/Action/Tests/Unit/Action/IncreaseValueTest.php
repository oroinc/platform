<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\IncreaseValue;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class IncreaseValueTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var IncreaseValue */
    private $action;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new class(new ContextAccessor()) extends IncreaseValue {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };
        $this->action->setDispatcher($this->eventDispatcher);
    }

    public function testInitialize()
    {
        $options = [
            'attribute' => new PropertyPath('test'),
            'value' => 3
        ];

        static::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        static::assertEquals($options, $this->action->xgetOptions());
    }

    public function testInitializeNoParametersException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute parameter is required.');

        $this->action->initialize([]);
    }

    public function testInitializeNoAttributeException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be defined.');

        $this->action->initialize(['test' => 'test']);
    }

    public function testInitializeInvalidAttributeException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

        $this->action->initialize(['attribute' => 'test']);
    }

    public function testInitializeInvalidValueExceptionWithString()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Value must be integer.');

        $this->action->initialize(['attribute' => new PropertyPath('test'), 'value' => 'string']);
    }

    public function testInitializeInvalidValueExceptionWithPropertyPath()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Value must be integer.');

        $this->action->initialize(['attribute' => new PropertyPath('test'), 'value' => new PropertyPath('test2')]);
    }

    public function testExecuteActionWithoutValueParam()
    {
        $options = ['attribute' => new PropertyPath('test')];
        $context = new StubStorage(['test' => 100]);

        $this->action->initialize($options);
        $this->action->execute($context);

        static::assertEquals(['test' => 101], $context->getValues());
    }

    /**
     * @dataProvider executeActionDapaProvider
     *
     * @param int $value
     * @param int $expected
     */
    public function testExecuteAction($value, $expected)
    {
        $options = ['attribute' => new PropertyPath('test'), 'value' => $value];
        $context = new StubStorage(['test' => 100]);

        $this->action->initialize($options);
        $this->action->execute($context);

        static::assertEquals(['test' => $expected], $context->getValues());
    }

    /**
     * @return \Generator
     */
    public function executeActionDapaProvider()
    {
        yield 'positive value' => ['value' => 500, 'expected' => 600];
        yield 'negative value' => ['value' => -500, 'expected' => -400];
        yield 'zero value' => ['value' => 0, 'expected' => 100];
    }
}
