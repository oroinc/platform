<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator\Condition;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConfigExpressionCondition;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;

class ConfigExpressionConditionTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $condition    = new ConfigExpressionCondition(['@true' => null]);
        $visitContext = $this->setUpVisitContext('echo 123;');

        $condition->visit($visitContext);

        $strategy = new DefaultGeneratorStrategy();
        $this->assertSame(
<<<CLASS
class LayoutUpdateClass implements \Oro\Component\ConfigExpression\ExpressionAssemblerAwareInterface
{
    private \$expressionAssembler;

    public function updateLayout(\$layoutManipulator, \$item)
    {
            if (\$this->expressionAssembler) {
                \$expr = \$this->expressionAssembler->assemble(array (
          '@true' => NULL,
        ));
                \$context = ['context' => \$item->getContext()];
                if (\$expr instanceof \Oro\Component\ConfigExpression\ExpressionInterface && \$expr->evaluate(\$context)) {
                    echo 123;
                }
            }
    }

    public function setAssembler(\Oro\Component\ConfigExpression\ExpressionAssembler \$assembler)
    {
        \$this->expressionAssembler = \$assembler;
    }
}
CLASS
        ,
        $strategy->generate($visitContext->getClass()));
    }
    //codingStandardsIgnoreEnd

    /**
     * @param string $body
     *
     * @return VisitContext
     */
    protected function setUpVisitContext($body)
    {
        $phpClass = PhpClass::create('LayoutUpdateClass');

        $method = PhpMethod::create(LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME);
        $method->addParameter(PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_MANIPULATOR));
        $method->addParameter(PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM));
        $method->setBody($body);
        $phpClass->setMethod($method);

        return new VisitContext($phpClass);
    }
}
