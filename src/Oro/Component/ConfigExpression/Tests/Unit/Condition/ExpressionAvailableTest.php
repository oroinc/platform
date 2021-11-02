<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition\ExpressionAvailable;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;

class ExpressionAvailableTest extends \PHPUnit\Framework\TestCase
{
    private const NAME = 'test_condition_name';
    private const TEST_TYPE = 'test_type';

    /** @var ExpressionAvailable */
    private $condition;

    protected function setUp(): void
    {
        $factory = $this->createMock(FactoryWithTypesInterface::class);
        $factory->expects($this->any())
            ->method('isTypeExists')
            ->willReturnCallback(fn ($type) => self::TEST_TYPE === $type);

        $this->condition = new ExpressionAvailable($factory, self::NAME);
    }

    public function testGetName()
    {
        $this->assertEquals(self::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, bool $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertSame($expectedResult, $this->condition->evaluate([]));
    }

    public function evaluateDataProvider(): array
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

    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 element, but 0 given.');

        $this->condition->initialize([]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(?string $message, array $expected)
    {
        $this->condition->initialize([self::TEST_TYPE]);
        $this->condition->setMessage($message);

        $this->assertEquals($expected, $this->condition->toArray());
    }

    public function toArrayDataProvider(): array
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
     */
    public function testCompile(?string $message, string $expected)
    {
        $this->condition->initialize([self::TEST_TYPE]);
        $this->condition->setMessage($message);

        $this->assertEquals($expected, $this->condition->compile('$factory'));
    }

    public function compileDataProvider(): array
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
