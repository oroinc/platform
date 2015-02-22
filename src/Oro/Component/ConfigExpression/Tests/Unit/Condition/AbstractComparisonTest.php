<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\Condition\AbstractComparison;
use Oro\Component\ConfigExpression\PropertyAccess\PropertyPath;

class AbstractComparisonTest extends \PHPUnit_Framework_TestCase
{
    /** @var AbstractComparison|\PHPUnit_Framework_MockObject_MockObject */
    protected $condition;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextAccessor;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock('Oro\Component\ConfigExpression\ContextAccessorInterface');
        $this->condition       = $this->getMockBuilder('Oro\Component\ConfigExpression\Condition\AbstractComparison')
            ->getMockForAbstractClass();
        $this->condition->setContextAccessor($this->contextAccessor);
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, array $context, $expectedValue)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));

        $right = end($options);
        $left  = reset($options);

        $keys     = array_keys($context);
        $rightKey = end($keys);
        $leftKey  = reset($keys);

        $this->contextAccessor->expects($this->at(0))
            ->method('getValue')
            ->with($context, $left)
            ->will($this->returnValue($context[$leftKey]));

        $this->contextAccessor->expects($this->at(1))
            ->method('getValue')
            ->with($context, $right)
            ->will($this->returnValue($context[$rightKey]));

        $this->condition->expects($this->once())
            ->method('doCompare')
            ->with($context[$leftKey], $context[$rightKey])
            ->will($this->returnValue($expectedValue));

        $this->assertEquals($expectedValue, $this->condition->evaluate($context));
    }

    public function evaluateDataProvider()
    {
        return [
            [
                ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')],
                ['foo' => 'fooValue', 'bar' => 'barValue'],
                true
            ],
            [
                [new PropertyPath('foo'), new PropertyPath('bar')],
                ['foo' => 'fooValue', 'bar' => 'barValue'],
                true
            ],
            [
                ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')],
                ['foo' => 'fooValue', 'bar' => 'barValue'],
                false
            ],
            [
                [new PropertyPath('foo'), new PropertyPath('bar')],
                ['foo' => 'fooValue', 'bar' => 'barValue'],
                false
            ]
        ];
    }

    public function testInitializeSuccess()
    {
        $this->assertSame($this->condition, $this->condition->initialize(['left' => 'foo', 'right' => 'bar']));
        $this->assertAttributeEquals('foo', 'left', $this->condition);
        $this->assertAttributeEquals('bar', 'right', $this->condition);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "right" is required.
     */
    public function testInitializeFailsWithEmptyRightOption()
    {
        $this->condition->initialize(
            [
                'foo'  => 'bar',
                'left' => 'foo'
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "left" is required.
     */
    public function testInitializeFailsWithEmptyLeftOption()
    {
        $this->condition->initialize(
            [
                'right' => 'foo',
                'foo'   => 'bar',
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 2 elements, but 0 given.
     */
    public function testInitializeFailsWithInvalidOptionsCount()
    {
        $this->condition->initialize([]);
    }

    public function testAddError()
    {
        $context = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $options = ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')];

        $left  = $options['left'];
        $right = $options['right'];

        $keys     = array_keys($context);
        $rightKey = end($keys);
        $leftKey  = reset($keys);

        $this->condition->initialize($options);
        $message = 'Compare {{ left }} with {{ right }}.';
        $this->condition->setMessage($message);

        $this->contextAccessor->expects($this->at(0))
            ->method('getValue')
            ->with($context, $left)
            ->will($this->returnValue($context[$leftKey]));

        $this->contextAccessor->expects($this->at(1))
            ->method('getValue')
            ->with($context, $right)
            ->will($this->returnValue($context[$rightKey]));

        $this->condition->expects($this->once())
            ->method('doCompare')
            ->with($context[$leftKey], $context[$rightKey])
            ->will($this->returnValue(false));

        $this->contextAccessor->expects($this->at(2))
            ->method('getValue')
            ->with($context, $left)
            ->will($this->returnValue($context[$leftKey]));

        $this->contextAccessor->expects($this->at(3))
            ->method('getValue')
            ->with($context, $right)
            ->will($this->returnValue($context[$rightKey]));

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            [
                'message'    => $message,
                'parameters' => ['{{ left }}' => $context[$leftKey], '{{ right }}' => $context[$rightKey]]
            ],
            $errors->get(0)
        );
    }
}
