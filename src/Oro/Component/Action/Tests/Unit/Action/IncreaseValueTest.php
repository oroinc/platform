<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\IncreaseValue;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class IncreaseValueTest extends TestCase
{
    private IncreaseValue $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new IncreaseValue(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitialize(): void
    {
        $options = [
            'attribute' => new PropertyPath('test'),
            'value' => 3
        ];

        self::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function testInitializeNoParametersException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute parameter is required.');

        $this->action->initialize([]);
    }

    public function testInitializeNoAttributeException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be defined.');

        $this->action->initialize(['test' => 'test']);
    }

    public function testInitializeInvalidAttributeException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

        $this->action->initialize(['attribute' => 'test']);
    }

    public function testInitializeInvalidValueExceptionWithString(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Value must be integer.');

        $this->action->initialize(['attribute' => new PropertyPath('test'), 'value' => 'string']);
    }

    public function testInitializeInvalidValueExceptionWithPropertyPath(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Value must be integer.');

        $this->action->initialize(['attribute' => new PropertyPath('test'), 'value' => new PropertyPath('test2')]);
    }

    public function testExecuteActionWithoutValueParam(): void
    {
        $options = ['attribute' => new PropertyPath('test')];
        $context = new StubStorage(['test' => 100]);

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals(['test' => 101], $context->getValues());
    }

    /**
     * @dataProvider executeActionDataProvider
     */
    public function testExecuteAction(int $value, int $expected): void
    {
        $options = ['attribute' => new PropertyPath('test'), 'value' => $value];
        $context = new StubStorage(['test' => 100]);

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals(['test' => $expected], $context->getValues());
    }

    public function executeActionDataProvider(): array
    {
        return [
            'positive value' => ['value' => 500, 'expected' => 600],
            'negative value' => ['value' => -500, 'expected' => -400],
            'zero value'     => ['value' => 0, 'expected' => 100]
        ];
    }
}
