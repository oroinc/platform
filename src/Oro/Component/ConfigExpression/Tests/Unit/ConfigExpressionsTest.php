<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\ConfigExpression\ConfigExpressions;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\ConfigExpression\ExpressionFactoryInterface;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;

class ConfigExpressionsTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigExpressions */
    private $language;

    protected function setUp(): void
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
        $expr = [
            '@empty' => [
                ['@trim' => '$foo']
            ]
        ];

        $this->assertTrue($this->language->evaluate($expr, $context));
    }

    public function testEvaluateByExpression()
    {
        $context = ['foo' => ' '];
        $expr = [
            '@empty' => [
                ['@trim' => '$foo']
            ]
        ];

        $this->assertTrue($this->language->evaluate($this->language->getExpression($expr), $context));
    }

    public function testSetAssembler()
    {
        $assembler = $this->createMock(AssemblerInterface::class);
        $this->language->setAssembler($assembler);
        $this->assertSame($assembler, $this->language->getAssembler());
    }

    public function testSetFactory()
    {
        $factory = $this->createMock(ExpressionFactoryInterface::class);
        $this->language->setFactory($factory);
        $this->assertSame($factory, $this->language->getFactory());
    }

    public function testSetContextAccessor()
    {
        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $this->language->setContextAccessor($contextAccessor);
        $this->assertSame($contextAccessor, $this->language->getContextAccessor());
    }

    public function testAddExtension()
    {
        $factory = $this->createMock(ExpressionFactory::class);
        $this->language->setFactory($factory);

        $extension = $this->createMock(ExtensionInterface::class);

        $factory->expects($this->once())
            ->method('addExtension')
            ->with($this->identicalTo($extension));

        $this->language->addExtension($extension);
    }
}
