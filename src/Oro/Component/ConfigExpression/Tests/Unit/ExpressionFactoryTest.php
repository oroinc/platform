<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\ConfigExpression\Extension\DependencyInjection\DependencyInjectionExtension;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;

class ExpressionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionFactory */
    protected $factory;

    /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextAccessor;

    /** @var ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $extension;

    protected function setUp()
    {
        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $this->extension = $this->createMock(ExtensionInterface::class);

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

        $this->assertNotSame(
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

        $this->assertNotSame(
            $expr,
            $this->factory->create('test', $options)
        );
    }

    public function testGetTypes()
    {
        $types = ['test_name' => 'test_service_id'];
        /** @var DependencyInjectionExtension|\PHPUnit\Framework\MockObject\MockObject $newExtension */
        $newExtension = $this->getMockBuilder(DependencyInjectionExtension::class)
            ->disableOriginalConstructor()
            ->getMock();
        $newExtension->expects($this->once())->method('getServiceIds')->willReturn($types);
        $this->factory->addExtension($newExtension);

        $this->assertSame($types, $this->factory->getTypes());
    }

    public function testIsTypeExists()
    {
        $this->extension->expects($this->exactly(2))
            ->method('hasExpression')
            ->willReturnMap(
                [
                    ['not_exists', false],
                    ['exists', true]
                ]
            );

        $this->assertFalse($this->factory->isTypeExists('not_exists'));
        $this->assertTrue($this->factory->isTypeExists('exists'));
    }
}
