<?php
declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ExpressionConditionVisitor;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\PhpUtils\ClassGenerator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionConditionVisitorTest extends \PHPUnit\Framework\TestCase
{
    public function testVisit()
    {
        $expression = $this->createMock(ParsedExpression::class);

        $expressionLanguage = $this->createMock(ExpressionLanguage::class);
        $expressionLanguage->expects(self::once())
            ->method('compile')
            ->with($expression)
            ->willReturn('(true == $context["enabled"])');

        $condition = new ExpressionConditionVisitor($expression, $expressionLanguage);
        $phpClass = new ClassGenerator('Test\LayoutUpdateClass');
        $visitContext = new VisitContext($phpClass);

        $method = $phpClass->addMethod(LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME);
        $method->addParameter(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_MANIPULATOR);
        $method->addParameter(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM);

        $condition->startVisit($visitContext);
        $visitContext->appendToUpdateMethodBody('echo 123;');
        $condition->endVisit($visitContext);

        $method->setBody($visitContext->getUpdateMethodBody());

        self::assertSame(
            <<<CLASS
namespace Test;

class LayoutUpdateClass implements \Oro\Component\Layout\IsApplicableLayoutUpdateInterface
{
    public function updateLayout(\$layoutManipulator, \$item)
    {
        if (!\$this->isApplicable(\$item->getContext())) {
            return;
        }

        echo 123;
    }

    public function isApplicable(\Oro\Component\Layout\ContextInterface \$context)
    {
        return (true == \$context["enabled"]);
    }
}

CLASS
            ,
            $visitContext->getClass()->print()
        );
    }
}
