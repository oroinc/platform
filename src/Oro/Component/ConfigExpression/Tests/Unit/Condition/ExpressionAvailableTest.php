<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition\ExpressionAvailable;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;

class ExpressionAvailableTest extends \PHPUnit\Framework\TestCase
{
    const NAME = 'test_condition_name';
    const TEST_TYPE = 'test_type';

    /** @var FactoryWithTypesInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    /** @var ExpressionAvailable */
    protected $condition;

    protected function setUp()
    {
        $this->factory = $this->createMock(FactoryWithTypesInterface::class);
        $this->factory->expects($this->any())
            ->method('isTypeExists')
            ->willReturnCallback(
                function ($type) {
                    return $type === self::TEST_TYPE;
                }
            );

        $this->condition = new ExpressionAvailable($this->factory, self::NAME);
    }

    protected function tearDown()
    {
        unset($this->condition, $this->factory);
    }

    public function testGetName()
    {
        $this->assertEquals(self::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param array $options
     * @param $expectedResult
     */
    public function testEvaluate(array $options, $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            [
                'options' => [self::TEST_TYPE],
                'expectedResult' => true
            ],
            [
                'options' => ['unknown'],
                'expectedResult' => false
            ]
        ];
    }

    public function testAddError()
    {
        $message = 'Error message.';

        $this->condition->initialize(['test']);
        $this->condition->setMessage($message);

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate([], $errors));
        $this->assertCount(1, $errors);
        $this->assertEquals(['message' => $message, 'parameters' => ['{{ type }}' => 'test']], $errors->first());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->condition->initialize([]);
    }

    /**
     * @dataProvider toArrayDataProvider
     *
     * @param string $message
     * @param array $expected
     */
    public function testToArray($message, array $expected)
    {
        $this->condition->initialize([self::TEST_TYPE]);
        $this->condition->setMessage($message);

        $this->assertEquals($expected, $this->condition->toArray());
    }

    /**
     * @return array
     */
    public function toArrayDataProvider()
    {
        return [
            [
                'message' => null,
                'expected' => [
                    '@' . self::NAME => ['parameters' => [self::TEST_TYPE]]
                ]
            ],
            [
                'message' => 'Test',
                'expected' => [
                    '@' . self::NAME => ['parameters' => [self::TEST_TYPE], 'message' => 'Test']
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     *
     * @param string $message
     * @param string $expected
     */
    public function testCompile($message, $expected)
    {
        $this->condition->initialize([self::TEST_TYPE]);
        $this->condition->setMessage($message);

        $this->assertEquals($expected, $this->condition->compile('$factory'));
    }

    /**
     * @return array
     */
    public function compileDataProvider()
    {
        return [
            [
                'message'  => null,
                'expected' => sprintf('$factory->create(\'%s\', [\'%s\'])', self::NAME, self::TEST_TYPE)
            ],
            [
                'message'  => 'Test',
                'expected' => sprintf(
                    '$factory->create(\'%s\', [\'%s\'])->setMessage(\'%s\')',
                    self::NAME,
                    self::TEST_TYPE,
                    'Test'
                )
            ]
        ];
    }
}
