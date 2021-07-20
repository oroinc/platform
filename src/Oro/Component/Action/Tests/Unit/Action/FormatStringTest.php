<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Action\Action\FormatString;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class FormatStringTest extends \PHPUnit\Framework\TestCase
{
    const ATTRIBUTE_PATH = 'attribute';
    const ARGUMENTS_PATH = 'arguments';

    /** @var FormatString */
    protected $action;

    /** @var string */
    protected $testString = 'some "%param1%" test "%param2%" string';

    /** @var array */
    protected $testArguments = ['param1' => 'first', 'param2' => 'second'];

    /** @var string */
    protected $expectedString = 'some "first" test "second" string';

    protected function setUp(): void
    {
        $this->action = new class(new ContextAccessor()) extends FormatString {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->action);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->action->initialize($options);

        static::assertEquals($options, $this->action->xgetOptions());
    }

    public function optionsDataProvider(): array
    {
        return [
            'only string' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => 'some test string'
                ],
                'expected' => 'some test string',
            ],
            'array arguments' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => $this->testString,
                    'arguments' => $this->testArguments,
                ],
                'expected' => $this->expectedString,
            ],
            'property path array arguments' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => $this->testString,
                    'arguments' => new PropertyPath(self::ARGUMENTS_PATH),
                ],
                'expected' => $this->expectedString,
                'arguments' => $this->testArguments,
            ],
            'property path collection arguments' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => $this->testString,
                    'arguments' => new PropertyPath(self::ARGUMENTS_PATH),
                ],
                'expected' => $this->expectedString,
                'arguments' => new ArrayCollection($this->testArguments),
            ],
        ];
    }

    /**
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            'no attribute' => [
                'options' => [],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribute name parameter is required',
            ],
            'incorrect attribute' => [
                'options' => [
                    'attribute' => 'string'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribute must be valid property definition',
            ],
            'no string' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'String parameter must be specified',
            ],
            'incorrect arguments' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => $this->testString,
                    'arguments' => 'not array',
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Argument parameter must be either array or PropertyPath',
            ],
        ];
    }

    /**
     * @param array $options
     * @param string $expected
     * @param mixed $arguments
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options, $expected, $arguments = null)
    {
        $context = new ItemStub();
        if (null !== $arguments) {
            $argumentsPath = self::ARGUMENTS_PATH;
            $context->$argumentsPath = $arguments;
        }

        $this->action->initialize($options);
        $this->action->execute($context);

        $attributePath = self::ATTRIBUTE_PATH;
        static::assertEquals($expected, $context->$attributePath);
    }

    public function testNotTraversableArguments()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Argument parameter must be traversable');

        $options = [
            'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
            'string' => $this->testString,
            'arguments' => new PropertyPath(self::ARGUMENTS_PATH),
        ];

        $context = new ItemStub();
        $argumentsPath = self::ARGUMENTS_PATH;
        $context->$argumentsPath = 'not_traversable_value';

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
