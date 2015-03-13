<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator\Visitor;

use CG\Generator\PhpClass;
use CG\Core\DefaultGeneratorStrategy;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\Visitor\ElementDependentVisitor;

class ElementDependentVisitorTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $conditionObject = new ElementDependentVisitor('header');

        $phpClass     = PhpClass::create('LayoutUpdateClass');
        $visitContext = new VisitContext($phpClass);

        $conditionObject->startVisit($visitContext);
        $conditionObject->endVisit($visitContext);

        $strategy = new DefaultGeneratorStrategy();
        $this->assertSame(
<<<CONTENT
class LayoutUpdateClass implements \Oro\Bundle\LayoutBundle\Layout\Generator\ElementDependentLayoutUpdateInterface
{
    public function getElement()
    {
        return 'header';
    }
}
CONTENT
            ,
            $strategy->generate($visitContext->getClass())
        );
    }
    // @codingStandardsIgnoreEnd
}
