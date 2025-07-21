<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\Condition\Blank;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Exception\UnexpectedTypeException;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\ConfigExpression\Extension\DependencyInjection\DependencyInjectionExtension;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExpressionFactoryTest extends TestCase
{
    private ContextAccessorInterface&MockObject $contextAccessor;
    private ExtensionInterface&MockObject $extension;
    private ExpressionFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $this->extension = $this->createMock(ExtensionInterface::class);

        $this->factory = new ExpressionFactory($this->contextAccessor);
        $this->factory->addExtension($this->extension);
    }

    public function testCreateNoExpression(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The expression "test" does not exist.');

        $this->extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->willReturn(false);
        $this->extension->expects($this->never())
            ->method('getExpression');

        $this->factory->create('test');
    }

    public function testCreateIncorrectExpressionType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid type of expression "test". Expected "%s", "stdClass" given.',
            ExpressionInterface::class
        ));

        $this->extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getExpression')
            ->with('test')
            ->willReturn(new \stdClass());

        $this->factory->create('test');
    }

    public function testCreate(): void
    {
        $options = ['key' => 'value'];
        $expr = $this->createMock(ExpressionInterface::class);

        $expr->expects($this->once())
            ->method('initialize')
            ->with($options);

        $this->extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getExpression')
            ->with('test')
            ->willReturn($expr);

        $this->assertNotSame(
            $expr,
            $this->factory->create('test', $options)
        );
    }

    public function testCreateContextAccessorAware(): void
    {
        $options = ['key' => 'value'];
        $expr = $this->createMock(Blank::class);

        $expr->expects($this->once())
            ->method('setContextAccessor')
            ->with($this->identicalTo($this->contextAccessor));
        $expr->expects($this->once())
            ->method('initialize')
            ->with($options);

        $this->extension->expects($this->once())
            ->method('hasExpression')
            ->with('test')
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getExpression')
            ->with('test')
            ->willReturn($expr);

        $this->assertNotSame(
            $expr,
            $this->factory->create('test', $options)
        );
    }

    public function testGetTypes(): void
    {
        $types = ['test_name' => 'test_service_id'];
        $newExtension = $this->createMock(DependencyInjectionExtension::class);
        $newExtension->expects($this->once())
            ->method('getServiceIds')
            ->willReturn($types);
        $this->factory->addExtension($newExtension);

        $this->assertSame($types, $this->factory->getTypes());
    }

    public function testIsTypeExists(): void
    {
        $this->extension->expects($this->exactly(2))
            ->method('hasExpression')
            ->willReturnMap([
                ['not_exists', false],
                ['exists', true]
            ]);

        $this->assertFalse($this->factory->isTypeExists('not_exists'));
        $this->assertTrue($this->factory->isTypeExists('exists'));
    }
}
