<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Visitor;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\ElementDependentVisitor;

class ElementDependentVisitorTest extends \PHPUnit\Framework\TestCase
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
class LayoutUpdateClass implements \Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface
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
