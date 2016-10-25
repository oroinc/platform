<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;

class ExpressionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionFactory */
    protected $factory;

    /** @var ContextAccessorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    /** @var ExtensionInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $extension;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock(ContextAccessorInterface::class);

        $this->extension = $this->getMock(ExtensionInterface::class);

        $this->factory = new ExpressionFactory($this->contextAccessor);
        $this->factory->addExtension($this->extension);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage The expression "test" does not exist.
     */
    public function testCreateNoExpression()
    {
        $this->extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->will($this->returnValue(false));
        $this->extension->expects($this->never())
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
        $this->extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
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
                [],
                '',
                true,
                true,
                true,
                ['setContextAccessor', 'initialize']
            );

        $expr->expects($this->never())
            ->method('setContextAccessor');
        $expr->expects($this->once())
            ->method('initialize')
            ->with($options);

        $this->extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
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

        $this->extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getExpression')
            ->with('test')
            ->will($this->returnValue($expr));

        $this->assertSame(
            $expr,
            $this->factory->create('test', $options)
        );
    }

    public function testAddGetExtensions()
    {
        /** @var ExtensionInterface $newExtension */
        $newExtension = $this->getMock(ExtensionInterface::class);
        $this->factory->addExtension($newExtension);

        $this->assertSame(
            [
                $this->extension,
                $newExtension
            ],
            $this->factory->getExtensions()
        );
    }
}
