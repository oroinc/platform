<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\Action\Action\Substring;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class SubstringTest extends \PHPUnit\Framework\TestCase
{
    const ATTRIBUTE_PATH = 'attribute';
    const TEST_STRING = 'some test string';

    /**
     * @var Substring
     */
    private $action;

    protected function setUp()
    {
        $this->action = new Substring(new ContextAccessor());

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->action->setDispatcher($dispatcher);
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
        $this->assertAttributeEquals($options, 'options', $this->action);
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

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
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
     * @param array $options
     * @param string $expected
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options, $expected)
    {
        $context = new ActionData([]);
        $this->action->initialize($options);
        $this->action->execute($context);

        $attributePath = self::ATTRIBUTE_PATH;
        $this->assertEquals($expected, $context->$attributePath);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
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
