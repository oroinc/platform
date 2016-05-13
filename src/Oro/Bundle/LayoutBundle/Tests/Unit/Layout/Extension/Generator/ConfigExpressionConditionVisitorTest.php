<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Core\DefaultGeneratorStrategy;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;

use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ConfigExpressionConditionVisitor;

class ConfigExpressionConditionVisitorTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $condition    = new ConfigExpressionConditionVisitor(new Condition\TrueCondition());
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
class LayoutUpdateClass implements \Oro\Component\ConfigExpression\ExpressionFactoryAwareInterface
{
    private \$expressionFactory;

    public function updateLayout(\$layoutManipulator, \$item)
    {
        if (null === \$this->expressionFactory) {
            throw new \RuntimeException('Missing expression factory for layout update');
        }

        \$expr = \$this->expressionFactory->create('true', []);
        \$context = ['context' => \$item->getContext()];
        if (\$expr->evaluate(\$context)) {
            echo 123;
        }
    }

    public function setExpressionFactory(\Oro\Component\ConfigExpression\ExpressionFactoryInterface \$expressionFactory)
    {
        \$this->expressionFactory = \$expressionFactory;
    }
}
CLASS
        ,
        $strategy->generate($visitContext->getClass()));
    }
    //codingStandardsIgnoreEnd
}
