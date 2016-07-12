<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Core\DefaultGeneratorStrategy;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;

use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ConfigExpressionConditionVisitor;

class ConfigExpressionConditionVisitorTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $expression = $this->getMockBuilder(ParsedExpression::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $expressionLanguage = $this->getMock(ExpressionLanguage::class);
        $expressionLanguage->expects($this->once())
            ->method('compile')
            ->with($expression)
            ->willReturn('(true == $context["enabled"])');

        $condition = new ConfigExpressionConditionVisitor($expression, $expressionLanguage);
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
class LayoutUpdateClass
{
    public function updateLayout(\$layoutManipulator, \$item)
    {
        \$context = \$item->getContext();
        if ((true == \$context["enabled"])) {
            echo 123;
        }
    }
}
CLASS
            ,
            $strategy->generate($visitContext->getClass())
        );
    }
    //codingStandardsIgnoreEnd
}
