<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\ConfigExpressions;

class ConfigExpressionsTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigExpressions */
    protected $language;

    protected function setUp()
    {
        $this->language = new ConfigExpressions();
    }

    public function testEvaluateNull()
    {
        $this->assertNull($this->language->evaluate(null, []));
    }

    public function testEvaluateEmpty()
    {
        $this->assertNull($this->language->evaluate([], []));
    }

    public function testEvaluateByConfiguration()
    {
        $context = ['foo' => ' '];
        $expr    = [
            '@empty' => [
                ['@trim' => '$foo']
            ]
        ];

        $this->assertTrue($this->language->evaluate($expr, $context));
    }

    public function testEvaluateByExpression()
    {
        $context = ['foo' => ' '];
        $expr    = [
            '@empty' => [
                ['@trim' => '$foo']
            ]
        ];

        $this->assertTrue($this->language->evaluate($this->language->getExpression($expr), $context));
    }

    public function testSetAssembler()
    {
        $assembler = $this->createMock('Oro\Component\ConfigExpression\AssemblerInterface');
        $this->language->setAssembler($assembler);
        $this->assertSame($assembler, $this->language->getAssembler());
    }

    public function testSetFactory()
    {
        $factory = $this->createMock('Oro\Component\ConfigExpression\ExpressionFactoryInterface');
        $this->language->setFactory($factory);
        $this->assertSame($factory, $this->language->getFactory());
    }

    public function testSetContextAccessor()
    {
        $contextAccessor = $this->createMock('Oro\Component\ConfigExpression\ContextAccessorInterface');
        $this->language->setContextAccessor($contextAccessor);
        $this->assertSame($contextAccessor, $this->language->getContextAccessor());
    }

    public function testAddExtension()
    {
        $factory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->language->setFactory($factory);

        $extension = $this->createMock('Oro\Component\ConfigExpression\Extension\ExtensionInterface');

        $factory->expects($this->once())
            ->method('addExtension')
            ->with($this->identicalTo($extension));

        $this->language->addExtension($extension);
    }
}
