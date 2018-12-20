<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;

class AbstractCompositeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Condition\AbstractComposite */
    protected $condition;

    protected function setUp()
    {
        $this->condition = $this->getMockForAbstractClass(
            'Oro\Component\ConfigExpression\Condition\AbstractComposite'
        );
    }

    public function testInitializeSuccess()
    {
        $operands = [$this->createMock('Oro\Component\ConfigExpression\ExpressionInterface')];

        $this->assertSame($this->condition, $this->condition->initialize($operands));
        $this->assertAttributeEquals($operands, 'operands', $this->condition);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have at least one element
     */
    public function testInitializeFailsWithEmptyElements()
    {
        $this->condition->initialize([]);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid type of option "0". Expected "Oro\Component\ConfigExpression\ExpressionInterface", "string" given.
     */
    // @codingStandardsIgnoreEnd
    public function testInitializeFailsWithScalarElement()
    {
        $this->condition->initialize(['anything']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid type of option "0". Expected "Oro\Component\ConfigExpression\ExpressionInterface", "stdClass" given.
     */
    // @codingStandardsIgnoreEnd
    public function testInitializeFailsWithWrongInstanceElement()
    {
        $this->condition->initialize([new \stdClass]);
    }
}
