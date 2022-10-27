<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\Action\Action\Substring;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class SubstringTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_PATH = 'attribute';
    private const TEST_STRING = 'some test string';

    /** @var Substring */
    private $action;

    protected function setUp(): void
    {
        $this->action = new Substring(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testInitialize()
    {
        $options = [
            'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
            'string' => 'some',
            'startPos' => 3,
            'length' => 7
        ];

        $this->action->initialize($options);
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
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
            'not integer length' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => 'some',
                    'length' => 'aaa'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'length option must be integer',
            ],
            'not integer startPos' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => 'some',
                    'startPos' => 'aaa'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'startPos option must be integer',
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options, string $expected)
    {
        $context = new ActionData([]);
        $this->action->initialize($options);
        $this->action->execute($context);

        $attributePath = self::ATTRIBUTE_PATH;
        self::assertEquals($expected, $context->{$attributePath});
    }

    public function optionsDataProvider(): array
    {
        return [
            'no startPos and length given' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => self::TEST_STRING
                ],
                'excepted' => self::TEST_STRING
            ],
            'startPos given' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => self::TEST_STRING,
                    'startPos' => 5
                ],
                'excepted' => 'test string'
            ],
            'startPos and length given' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => self::TEST_STRING,
                    'startPos' => 5,
                    'length' => 4
                ],
                'excepted' => 'test'
            ],
        ];
    }
}
