<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Extension;

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
            'Oro\Component\ConfigExpression\ExpressionInterface',
            $extension->getExpression('test')
        );
    }

    public function testGetUnknownExpression()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The expression "unknown" can not be loaded by this extension.');

        $extension = $this->getAbstractExtension();
        $extension->getExpression('unknown');
    }

    public function testLoadInvalidExpressions()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\ConfigExpression\ExpressionInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([123]);
        $extension->hasExpression('test');
    }

    protected function getAbstractExtension()
    {
        $expr = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test'));

        return new AbstractExtensionStub([$expr]);
    }
}
