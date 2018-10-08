<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\IncreaseValue;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class IncreaseValueTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var IncreaseValue */
    private $action;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new IncreaseValue(new ContextAccessor(), $this->container);
        $this->action->setDispatcher($this->eventDispatcher);
    }

    public function testInitialize()
    {
        $options = [
            'attribute' => new PropertyPath('test'),
            'value' => 3
        ];

        $this->assertInstanceOf(ActionInterface::class, $this->action->initialize($options));

        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute parameter is required.
     */
    public function testInitializeNoParametersException()
    {
        $this->action->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute must be defined.
     */
    public function testInitializeNoAttributeException()
    {
        $this->action->initialize(['test' => 'test']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute must be valid property definition.
     */
    public function testInitializeInvalidAttributeException()
    {
        $this->action->initialize(['attribute' => 'test']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Value must be integer.
     */
    public function testInitializeInvalidValueExceptionWithString()
    {
        $this->action->initialize(['attribute' => new PropertyPath('test'), 'value' => 'string']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Value must be integer.
     */
    public function testInitializeInvalidValueExceptionWithPropertyPath()
    {
        $this->action->initialize(['attribute' => new PropertyPath('test'), 'value' => new PropertyPath('test2')]);
    }

    public function testExecuteActionWithoutValueParam()
    {
        $options = ['attribute' => new PropertyPath('test')];
        $context = new StubStorage(['test' => 100]);

        $this->action->initialize($options);
        $this->action->execute($context);

        $this->assertEquals(['test' => 101], $context->getValues());
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

        $this->assertEquals(['test' => $expected], $context->getValues());
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
