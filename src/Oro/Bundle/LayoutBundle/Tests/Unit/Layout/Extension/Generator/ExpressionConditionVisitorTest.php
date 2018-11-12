<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ExpressionConditionVisitor;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionConditionVisitorTest extends \PHPUnit\Framework\TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $expression = $this->getMockBuilder(ParsedExpression::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $expressionLanguage = $this->createMock(ExpressionLanguage::class);
        $expressionLanguage->expects($this->once())
            ->method('compile')
            ->with($expression)
            ->willReturn('(true == $context["enabled"])');

        $condition = new ExpressionConditionVisitor($expression, $expressionLanguage);
        $phpClass = PhpClass::create('LayoutUpdateClass');
        $visitContext = new VisitContext($phpClass);

        $method = PhpMethod::create(LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME);
        $method->addParameter(PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_MANIPULATOR));
        $method->addParameter(PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM));

        $condition->startVisit($visitContext);
        $visitContext->getUpdateMethodWriter()->writeln('echo 123;');
        $condition->endVisit($visitContext);

        $method->setBody($visitContext->getUpdateMethodWriter()->getContent());
        $phpClass->setMethod($method);

        $strategy = new DefaultGeneratorStrategy();
        $this->assertSame(
<<<CLASS
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
            $strategy->generate($visitContext->getClass())
        );
    }
    //codingStandardsIgnoreEnd
}
