<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator\Visitor;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Core\DefaultGeneratorStrategy;

use Oro\Component\ConfigExpression\Condition;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\Visitor\ConfigExpressionConditionVisitor;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;

class ConfigExpressionConditionVisitorTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $condition    = new ConfigExpressionConditionVisitor(new Condition\True());
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
class LayoutUpdateClass implements \Oro\Bundle\LayoutBundle\Layout\Generator\ExpressionFactoryAwareInterface
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
