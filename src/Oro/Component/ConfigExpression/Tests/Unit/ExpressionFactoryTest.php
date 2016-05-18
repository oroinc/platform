<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\ExpressionFactory;

class ExpressionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionFactory */
    protected $factory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock('Oro\Component\ConfigExpression\ContextAccessorInterface');
        $this->factory         = new ExpressionFactory($this->contextAccessor);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage The expression "test" does not exist.
     */
    public function testCreateNoExpression()
    {
        $extension = $this->getMock('Oro\Component\ConfigExpression\Extension\ExtensionInterface');
        $this->factory->addExtension($extension);

        $extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->will($this->returnValue(false));
        $extension->expects($this->never())
            ->method('getExpression');

        $this->factory->create('test');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid type of expression "test". Expected "Oro\Component\ConfigExpression\ExpressionInterface", "stdClass" given.
     */
    // @codingStandardsIgnoreEnd
    public function testCreateIncorrectExpressionType()
    {
        $extension = $this->getMock('Oro\Component\ConfigExpression\Extension\ExtensionInterface');
        $this->factory->addExtension($extension);

        $extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->will($this->returnValue(true));
        $extension->expects($this->once())
            ->method('getExpression')
            ->with('test')
            ->will($this->returnValue(new \stdClass()));

        $this->factory->create('test');
    }

    public function testCreate()
    {
        $options = ['key' => 'value'];
        $expr    = $this
            ->getMockForAbstractClass(
                'Oro\Component\ConfigExpression\ExpressionInterface',
                array(),
                '',
                true,
                true,
                true,
                array('setContextAccessor', 'initialize')
            );

        $expr->expects($this->never())
            ->method('setContextAccessor');
        $expr->expects($this->once())
            ->method('initialize')
            ->with($options);

        $extension = $this->getMock('Oro\Component\ConfigExpression\Extension\ExtensionInterface');
        $this->factory->addExtension($extension);

        $extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->will($this->returnValue(true));
        $extension->expects($this->once())
            ->method('getExpression')
            ->with('test')
            ->will($this->returnValue($expr));

        $this->assertSame(
            $expr,
            $this->factory->create('test', $options)
        );
    }

    public function testCreateContextAccessorAware()
    {
        $options = ['key' => 'value'];
        $expr    = $this->getMockBuilder('Oro\Component\ConfigExpression\Condition\Blank')
            ->disableOriginalConstructor()
            ->getMock();

        $expr->expects($this->once())
            ->method('setContextAccessor')
            ->with($this->identicalTo($this->contextAccessor));
        $expr->expects($this->once())
            ->method('initialize')
            ->with($options);

        $extension = $this->getMock('Oro\Component\ConfigExpression\Extension\ExtensionInterface');
        $this->factory->addExtension($extension);

        $extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->will($this->returnValue(true));
        $extension->expects($this->once())
            ->method('getExpression')
            ->with('test')
            ->will($this->returnValue($expr));

        $this->assertSame(
            $expr,
            $this->factory->create('test', $options)
        );
    }
}
