<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Condition;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Condition\CollectionElementValueExists;
use Oro\Component\ConfigExpression\ContextAccessor;

class CollectionElementValueExistsTest extends \PHPUnit_Framework_TestCase
{
    /** @var CollectionElementValueExists */
    protected $condition;

    public function setUp()
    {
        $this->condition = new CollectionElementValueExists();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage)
    {
        $this->setExpectedException($exceptionName, $exceptionMessage);

        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            [
                'options' => [],
                'exceptionName' => 'Oro\Component\ConfigExpression\Exception\InvalidArgumentException',
                'exceptionMessage' => 'Options must have 2 or more elements, but 0 given.'
            ],
            [
                'options' => ['string', 'string'],
                'exceptionName' => 'Oro\Component\ConfigExpression\Exception\InvalidArgumentException',
                'exceptionMessage' => 'Option with index 0 must be property path.'
            ]
        ];
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param array $options
     * @param array $context
     * @param bool $expectedResult
     */
    public function testEvaluate(array $options, array $context, $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        $options = [new PropertyPath('foo.words'), new PropertyPath('data.name'), new PropertyPath('bar')];

        return [
            'in_array' => [
                'options' => $options,
                'context' => [
                    'foo' => [
                        'sth',
                        'words' => [
                            ['name' => 'worda'],
                            ['name' => 'wordb']
                        ],
                        'sth else'
                    ],
                    'bar' => 'wordb'
                ],
                'expectedResult' => true
            ],
            'not_in_array' => [
                'options' => $options,
                'context' => [
                    'foo' => [
                        'sth',
                        'words' => [
                            ['name' => 'worda'],
                            ['name' => 'wordb']
                        ],
                        'sth else'
                    ],
                    'bar' => 'wordc'
                ],
                'expectedResult' => false,
            ],
            'not_strict_in_array' => [
                'options' => $options,
                'context' => [
                    'foo' => [
                        'sth',
                        'words' => [
                            ['name' => '5'],
                            ['name' => '15']
                        ],
                        'sth else'
                    ],
                    'bar' => 15
                ],
                'expectedResult' => true,
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(CollectionElementValueExists::NAME, $this->condition->getName());
    }
}
