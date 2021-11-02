<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Extension;

use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Exception\UnexpectedTypeException;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\AbstractExtensionStub;

class AbstractExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testHasExpression()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasExpression('test'));
        $this->assertFalse($extension->hasExpression('unknown'));
    }

    public function testGetExpression()
    {
        $extension = $this->getAbstractExtension();
        $this->assertInstanceOf(
            ExpressionInterface::class,
            $extension->getExpression('test')
        );
    }

    public function testGetUnknownExpression()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The expression "unknown" can not be loaded by this extension.');

        $extension = $this->getAbstractExtension();
        $extension->getExpression('unknown');
    }

    public function testLoadInvalidExpressions()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected argument of type "%s", "integer" given.',
            ExpressionInterface::class
        ));

        $extension = new AbstractExtensionStub([123]);
        $extension->hasExpression('test');
    }

    private function getAbstractExtension(): AbstractExtensionStub
    {
        $expr = $this->createMock(ExpressionInterface::class);
        $expr->expects($this->any())
            ->method('getName')
            ->willReturn('test');

        return new AbstractExtensionStub([$expr]);
    }
}
