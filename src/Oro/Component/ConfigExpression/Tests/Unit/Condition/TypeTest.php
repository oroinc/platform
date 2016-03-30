<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;

class TypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var Condition\Type */
    protected $condition;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->condition = new Condition\Type();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals(Condition\Type::NAME, $this->condition->getName());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "left" must be property path
     */
    public function testInitializeWithException()
    {
        $this->condition->initialize([1, 2]);
    }

    /**
     * @param array $options
     * @param array $context
     * @param bool $expectedResult
     *
     * @dataProvider evaluateDataProvider
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
        return [
            'integer' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'integer'],
                'context'        => ['prop1' => 1],
                'expectedResult' => true
            ],
            'string' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'string'],
                'context'        => ['prop1' => 'string'],
                'expectedResult' => true
            ],
            'boolean' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'boolean'],
                'context'        => ['prop1' => false],
                'expectedResult' => true
            ],
            'array' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'array'],
                'context'        => ['prop1' => [2]],
                'expectedResult' => true
            ],
            'double' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'double'],
                'context'        => ['prop1' => 3.4],
                'expectedResult' => true
            ],
            'object' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'object'],
                'context'        => ['prop1' => new \stdClass()],
                'expectedResult' => true
            ],
            '\stdClass' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => '\stdClass'],
                'context'        => ['prop1' => new \stdClass()],
                'expectedResult' => true
            ],

            'no integer'  => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'integer'],
                'context'        => ['prop1' => 'string'],
                'expectedResult' => false
            ],
            'no string' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'string'],
                'context'        => ['prop1' => 5],
                'expectedResult' => false
            ],
            'no boolean' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'boolean'],
                'context'        => ['prop1' => 6],
                'expectedResult' => false
            ],
            'no array' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'array'],
                'context'        => ['prop1' => 7],
                'expectedResult' => false
            ],
            'no double' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'double'],
                'context'        => ['prop1' => 8],
                'expectedResult' => false
            ],
            'no object' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => 'object'],
                'context'        => ['prop1' => 9],
                'expectedResult' => false
            ],
            'no \stdClass' => [
                'options'        => ['left' => new PropertyPath('prop1'), 'right' => '\stdClass'],
                'context'        => ['prop1' => 10],
                'expectedResult' => false
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider errorMessagesProvider
     */
    public function testErrorMessages(array $inputData, array $expectedData)
    {
        $options = [new PropertyPath('prop'), $inputData['type']];
        $context = ['prop' => $inputData['value']];

        $this->condition->initialize($options);

        $this->condition->setMessage($inputData['message']);

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals($expectedData, $errors->get(0));
    }

    /**
     * @return array
     */
    public function errorMessagesProvider()
    {
        return [
            'integer value (scalar)' => [
                'input' => [
                    'type' => 'string',
                    'value' => 1,
                    'message' => 'Error message1',
                ],
                'expected' => [
                    'message' => 'Error message1',
                    'parameters' => [
                        '{{ value }}' => '(integer)1',
                        '{{ type }}' => 'string',
                    ]
                ],
            ],
            'string value (scalar)' => [
                'input' => [
                    'type' => 'integer',
                    'value' => 'str',
                    'message' => 'Error message2',
                ],
                'expected' => [
                    'message' => 'Error message2',
                    'parameters' => [
                        '{{ value }}' => '(string)str',
                        '{{ type }}' => 'integer',
                    ]
                ],
            ],
            'array value (!scalar)' => [
                'input' => [
                    'type' => 'integer',
                    'value' => ['str'],
                    'message' => 'Error message3',
                ],
                'expected' => [
                    'message' => 'Error message3',
                    'parameters' => [
                        '{{ value }}' => 'array',
                        '{{ type }}' => 'integer',
                    ]
                ],
            ],
            'object value (object)' => [
                'input' => [
                    'type' => 'integer',
                    'value' => new \stdClass(),
                    'message' => 'Error message4',
                ],
                'expected' => [
                    'message' => 'Error message4',
                    'parameters' => [
                        '{{ value }}' => '(object)stdClass',
                        '{{ type }}' => 'integer',
                    ]
                ],
            ],
        ];
    }
}
