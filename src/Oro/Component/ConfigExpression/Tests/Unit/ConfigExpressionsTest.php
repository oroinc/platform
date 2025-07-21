<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\ConfigExpression\ConfigExpressions;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\ConfigExpression\ExpressionFactoryInterface;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;
use PHPUnit\Framework\TestCase;

class ConfigExpressionsTest extends TestCase
{
    private ConfigExpressions $language;

    #[\Override]
    protected function setUp(): void
    {
        $this->language = new ConfigExpressions();
    }

    public function testEvaluateNull(): void
    {
        $this->assertNull($this->language->evaluate(null, []));
    }

    public function testEvaluateEmpty(): void
    {
        $this->assertNull($this->language->evaluate([], []));
    }

    public function testEvaluateByConfiguration(): void
    {
        $context = ['foo' => ' '];
        $expr = [
            '@empty' => [
                ['@trim' => '$foo']
            ]
        ];

        $this->assertTrue($this->language->evaluate($expr, $context));
    }

    public function testEvaluateByExpression(): void
    {
        $context = ['foo' => ' '];
        $expr = [
            '@empty' => [
                ['@trim' => '$foo']
            ]
        ];

        $this->assertTrue($this->language->evaluate($this->language->getExpression($expr), $context));
    }

    public function testSetAssembler(): void
    {
        $assembler = $this->createMock(AssemblerInterface::class);
        $this->language->setAssembler($assembler);
        $this->assertSame($assembler, $this->language->getAssembler());
    }

    public function testSetFactory(): void
    {
        $factory = $this->createMock(ExpressionFactoryInterface::class);
        $this->language->setFactory($factory);
        $this->assertSame($factory, $this->language->getFactory());
    }

    public function testSetContextAccessor(): void
    {
        $contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $this->language->setContextAccessor($contextAccessor);
        $this->assertSame($contextAccessor, $this->language->getContextAccessor());
    }

    public function testAddExtension(): void
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
