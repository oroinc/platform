<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Action\Action\FormatString;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class FormatStringTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_PATH = 'attribute';
    private const ARGUMENTS_PATH = 'arguments';

    private string $testString = 'some "%param1%" test "%param2%" string';
    private array $testArguments = ['param1' => 'first', 'param2' => 'second'];
    private string $expectedString = 'some "first" test "second" string';

    /** @var FormatString */
    private $action;

    protected function setUp(): void
    {
        $this->action = new FormatString(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->action->initialize($options);

        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
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
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exceptionName, string $exceptionMessage)
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
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options, string $expected, mixed $arguments = null)
    {
        $context = new ItemStub();
        if (null !== $arguments) {
            $argumentsPath = self::ARGUMENTS_PATH;
            $context->{$argumentsPath} = $arguments;
        }

        $this->action->initialize($options);
        $this->action->execute($context);

        $attributePath = self::ATTRIBUTE_PATH;
        self::assertEquals($expected, $context->{$attributePath});
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
        $context->{$argumentsPath} = 'not_traversable_value';

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
