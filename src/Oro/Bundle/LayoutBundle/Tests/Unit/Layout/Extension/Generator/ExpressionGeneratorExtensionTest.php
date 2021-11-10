<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ExpressionConditionVisitor;
use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ExpressionGeneratorExtension;
use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionLanguage|\PHPUnit\Framework\MockObject\MockObject */
    private $expressionLanguage;

    /** @var ExpressionGeneratorExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->expressionLanguage = $this->createMock(ExpressionLanguage::class);

        $this->extension = new ExpressionGeneratorExtension($this->expressionLanguage);
    }

    public function testNoConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionLanguage->expects($this->never())
            ->method('parse');

        $this->extension->prepare(
            new GeneratorData([]),
            $visitors
        );

        $this->assertCount(0, $visitors);
    }

    public function testEmptyConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionLanguage->expects($this->never())
            ->method('parse');

        $this->extension->prepare(
            new GeneratorData([ExpressionGeneratorExtension::NODE_CONDITIONS => null]),
            $visitors
        );

        $this->assertCount(0, $visitors);
    }

    public function testHasConditions()
    {
        $visitors = new VisitorCollection();

        $expression = $this->createMock(ParsedExpression::class);

        $this->expressionLanguage->expects($this->once())
            ->method('parse')
            ->with('true')
            ->willReturn($expression);

        $this->extension->prepare(
            new GeneratorData([ExpressionGeneratorExtension::NODE_CONDITIONS => 'true']),
            $visitors
        );

        $this->assertCount(1, $visitors);
        $this->assertInstanceOf(ExpressionConditionVisitor::class, $visitors->current());
    }

    public function testUnknownConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionLanguage->expects($this->once())
            ->method('parse')
            ->with('unknown')
            ->willReturn(null);

        $this->extension->prepare(
            new GeneratorData([ExpressionGeneratorExtension::NODE_CONDITIONS => 'unknown']),
            $visitors
        );

        $this->assertCount(0, $visitors);
    }

    public function testInvalidConditions()
    {
        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Syntax error: invalid conditions. some error at "conditions"');

        $visitors = new VisitorCollection();

        $this->expressionLanguage->expects($this->once())
            ->method('parse')
            ->with('true')
            ->willThrowException(new \Exception('some error'));

        $this->extension->prepare(
            new GeneratorData([ExpressionGeneratorExtension::NODE_CONDITIONS => 'true']),
            $visitors
        );
    }
}
